<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\SubjectConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    /**
     * Calculer la moyenne d'un élève pour une matière et une période
     */
    public function calculateSubjectAverage(User $student, Matiere $subject, string $period): float
    {
        $notes = Note::where('user_id', $student->id)
            ->where('matiere_id', $subject->id)
            ->where('periode', $period)
            ->get();

        if ($notes->isEmpty()) {
            return 0.0;
        }

        return round($notes->avg('valeur'), 2);
    }

    /**
     * Moyenne d'une matière pour le collège/lycée : (moyenne des devoirs + note de
     * composition) / 2, chacun pesant 50 %. Si un seul type d'évaluation est renseigné
     * (composition pas encore saisie, ou devoirs seuls en cours de période), la moyenne
     * repose uniquement sur les notes disponibles plutôt que de diviser artificiellement
     * par deux une moyenne partielle. Retourne null si aucune note.
     */
    public function calculateWeightedSubjectAverage(Collection $notes): ?float
    {
        $devoirs = $notes->where('type_evaluation', 'devoir')->pluck('valeur');
        $compositions = $notes->where('type_evaluation', 'composition')->pluck('valeur');

        $devoirsAverage = $devoirs->isNotEmpty() ? $devoirs->avg() : null;
        $compositionAverage = $compositions->isNotEmpty() ? $compositions->avg() : null;

        if ($devoirsAverage === null && $compositionAverage === null) {
            return null;
        }

        if ($devoirsAverage !== null && $compositionAverage !== null) {
            return round(($devoirsAverage + $compositionAverage) / 2, 2);
        }

        return round($devoirsAverage ?? $compositionAverage, 2);
    }

    /**
     * Matières réellement affectées à une classe (via PedagogicalAssignment), pour une
     * année scolaire donnée. Source de vérité unique du périmètre d'un bulletin : ne
     * jamais utiliser Matiere::all() qui inclurait des matières d'autres cycles/classes.
     */
    private function classroomSubjects(Classroom $classroom, ?int $schoolYearId): Collection
    {
        $matiereIds = PedagogicalAssignment::where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->pluck('matiere_id')
            ->unique();

        return Matiere::whereIn('id', $matiereIds)->orderBy('nom')->get();
    }

    /**
     * Détail par matière + moyenne générale pondérée d'un élève pour une classe/période.
     * Une matière sans aucune note sur la période n'entre pas dans le calcul de la
     * moyenne générale (son coefficient ne doit pas artificiellement la faire baisser),
     * mais reste listée pour affichage.
     *
     * @return array{subjects: array, general_average: float, total_coefficients: float}
     */
    private function computeAverageData(User $student, Classroom $classroom, string $period, ?int $schoolYearId): array
    {
        // Primaire (système "sunuBulletin") : chaque matière a son propre barème (note
        // max, ex. Mathématiques /80, Arabe /10) qui sert aussi de poids — moyenne
        // générale = somme des points obtenus / somme des barèmes des matières notées ×
        // 20. Fondamentalement différent du collège/lycée (coefficient appliqué à une
        // note toujours /20). N'active ce mode que si l'établissement a réellement
        // configuré au moins un barème pour le primaire cette année (usesBaremeSystem) :
        // un cycle 'primaire' sans configuration de barème continue sur le système
        // standard (coefficient=1 par défaut, équivalent à une moyenne /20 classique) —
        // n'impose jamais ce système à une classe de primaire qui ne l'utilise pas.
        return $this->usesBaremeSystem($classroom, $schoolYearId)
            ? $this->computeAverageDataPrimaire($student, $classroom, $period, $schoolYearId)
            : $this->computeAverageDataStandard($student, $classroom, $period, $schoolYearId);
    }

    /**
     * Publique : réutilisée par les formulaires de saisie de notes pour savoir s'il faut
     * afficher/valider un barème par matière plutôt que la note /20 classique.
     */
    public function usesBaremeSystem(Classroom $classroom, ?int $schoolYearId): bool
    {
        if ($classroom->cycle !== 'primaire' || ! $schoolYearId) {
            return false;
        }

        return SubjectConfiguration::where('school_year_id', $schoolYearId)
            ->where('is_active', true)
            ->whereNotNull('bareme')
            ->where(fn ($query) => $query->where('cycle', 'primaire')->orWhereNull('cycle'))
            ->exists();
    }

    private function computeAverageDataStandard(User $student, Classroom $classroom, string $period, ?int $schoolYearId): array
    {
        $subjectsData = [];
        $totalCoefficients = 0.0;
        $totalWeighted = 0.0;
        $useWeightedFormula = in_array($classroom->cycle, ['college', 'lycee'], true);

        foreach ($this->classroomSubjects($classroom, $schoolYearId) as $matiere) {
            $notes = Note::where('user_id', $student->id)
                ->where('matiere_id', $matiere->id)
                ->where('periode', $period)
                ->get();

            $coefficient = $this->resolveCoefficient($matiere, $classroom, $schoolYearId);
            $hasNotes = $notes->isNotEmpty();
            $average = 0.0;
            if ($hasNotes) {
                $average = $useWeightedFormula
                    ? ($this->calculateWeightedSubjectAverage($notes) ?? 0.0)
                    : round($notes->avg('valeur'), 2);
            }
            $weightedAverage = $hasNotes ? round($average * $coefficient, 2) : 0.0;

            $subjectsData[] = [
                'matiere' => $matiere->nom,
                'coefficient' => $coefficient,
                'bareme' => null,
                'notes' => $notes->pluck('valeur')->toArray(),
                'average' => $average,
                'weighted_average' => $weightedAverage,
                'appreciation' => $notes->last()?->appreciation ?? '',
            ];

            if ($hasNotes) {
                $totalCoefficients += $coefficient;
                $totalWeighted += $average * $coefficient;
            }
        }

        $generalAverage = $totalCoefficients > 0 ? round($totalWeighted / $totalCoefficients, 2) : 0.0;

        return [
            'subjects' => $subjectsData,
            'general_average' => $generalAverage,
            'total_coefficients' => $totalCoefficients,
        ];
    }

    /**
     * @return array{subjects: array, general_average: float, total_coefficients: float}
     */
    private function computeAverageDataPrimaire(User $student, Classroom $classroom, string $period, ?int $schoolYearId): array
    {
        $subjectsData = [];
        $totalBaremes = 0.0;
        $totalPoints = 0.0;

        foreach ($this->classroomSubjects($classroom, $schoolYearId) as $matiere) {
            $notes = Note::where('user_id', $student->id)
                ->where('matiere_id', $matiere->id)
                ->where('periode', $period)
                ->get();

            $bareme = $this->resolveBareme($matiere, $classroom, $schoolYearId);
            $hasNotes = $notes->isNotEmpty();
            // Somme des points obtenus (pas moyenne) : le primaire n'a qu'une composition
            // par matière et par période (EvaluationTypeScope::allowedFor('primaire')),
            // mais avg() reste correct si jamais plusieurs notes existent sur la période.
            $average = $hasNotes ? round($notes->avg('valeur'), 2) : 0.0;

            $subjectsData[] = [
                'matiere' => $matiere->nom,
                'coefficient' => $bareme,
                'bareme' => $bareme,
                'notes' => $notes->pluck('valeur')->toArray(),
                'average' => $average,
                // Les points obtenus SONT déjà la contribution pondérée (le barème est le
                // dénominateur, pas un multiplicateur) — contrairement à Moy.×Coef au
                // collège/lycée, ne jamais multiplier average par bareme ici.
                'weighted_average' => $average,
                'appreciation' => $notes->last()?->appreciation ?? '',
            ];

            if ($hasNotes) {
                $totalBaremes += $bareme;
                $totalPoints += $average;
            }
        }

        $generalAverage = $totalBaremes > 0 ? round($totalPoints / $totalBaremes * 20, 2) : 0.0;

        return [
            'subjects' => $subjectsData,
            'general_average' => $generalAverage,
            'total_coefficients' => $totalBaremes,
        ];
    }

    /**
     * Coefficient réellement applicable pour une matière/classe/année : l'écran
     * "Configuration pédagogique" > Matières & coefficients (SubjectConfiguration)
     * est la source de vérité quand un coefficient y a été configuré pour l'année
     * scolaire de l'élève — priorité au coefficient spécifique à la série de la classe
     * (lycée uniquement, ex. Maths coef. 4 en Série S vs coef. 2 en Série L), puis au
     * coefficient du cycle sans distinction de série, puis au coefficient "Tous les
     * cycles" (cycle = null) de cette même année. Repli sur Matiere::coefficient (champ
     * global, non versionné) si rien n'est configuré pour cette année — comportement
     * historique conservé pour ne pas casser les bulletins d'années sans configuration
     * dédiée.
     */
    public function resolveCoefficient(Matiere $matiere, Classroom $classroom, ?int $schoolYearId): float
    {
        if ($schoolYearId) {
            $configurations = SubjectConfiguration::where('matiere_id', $matiere->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->get();

            $match = $configurations->first(fn ($c) => $c->cycle === $classroom->cycle && $c->serie === $classroom->serie)
                ?? ($classroom->serie ? $configurations->first(fn ($c) => $c->cycle === $classroom->cycle && $c->serie === null) : null)
                ?? $configurations->firstWhere('cycle', null);

            if ($match) {
                return (float) $match->coefficient;
            }
        }

        return (float) ($matiere->coefficient ?? 1.0);
    }

    /**
     * Barème (note maximale) réellement applicable pour une matière de primaire, dans le
     * système "sunuBulletin" : configuré via SubjectConfiguration.bareme (même écran
     * "Matières & coefficients" que resolveCoefficient(), même priorité cycle spécifique
     * puis "Tous les cycles"). Repli sur le barème de base de la matière (Matiere::bareme,
     * /20 par défaut) si rien n'est configuré pour ce cycle/cette année, pour qu'une
     * matière de primaire jamais paramétrée reste malgré tout saisissable/calculable
     * plutôt que de casser le bulletin. Publique : réutilisée par la validation de
     * saisie de notes (GradeController) et les formulaires (max dynamique).
     */
    public function resolveBareme(Matiere $matiere, Classroom $classroom, ?int $schoolYearId): float
    {
        if ($schoolYearId) {
            $configurations = SubjectConfiguration::where('matiere_id', $matiere->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->whereNotNull('bareme')
                ->get();

            $match = $configurations->firstWhere('cycle', $classroom->cycle)
                ?? $configurations->firstWhere('cycle', null);

            if ($match) {
                return (float) $match->bareme;
            }
        }

        return (float) ($matiere->bareme ?? 20.0);
    }

    /**
     * Calculer le classement d'un élève dans sa classe pour une période. Utilise le
     * même calcul de moyenne générale que le bulletin (computeAverageData) : le
     * classement doit toujours être cohérent avec la moyenne affichée à l'élève.
     */
    public function calculateClassRank(User $student, Classroom $classroom, string $period, ?int $schoolYearId = null): int
    {
        $studentAverage = $this->computeAverageData($student, $classroom, $period, $schoolYearId)['general_average'];

        $students = User::role('eleve')
            ->whereHas('registrations', function ($query) use ($classroom) {
                $query->where('classroom_id', $classroom->id)
                    ->where('status', 'active');
            })
            ->get();

        $betterCount = $students
            ->filter(fn (User $s) => ! $s->is($student))
            ->map(fn (User $s) => $this->computeAverageData($s, $classroom, $period, $schoolYearId)['general_average'])
            ->filter(fn (float $average) => $average > $studentAverage)
            ->count();

        return $betterCount + 1;
    }

    /**
     * Bande de couleur/appréciation pour le tableau de bord élève/parent (code couleur
     * du cahier des charges : 0-9,99 Insuffisant, 10-11,99 Passable, 12-13,99 Assez
     * bien, 14-15,99 Bien, 16-20 Très Bien/Excellent). Distinct de getMention() (utilisé
     * par les bulletins officiels, seuils différents) : ne pas fusionner, ce sont deux
     * échelles différentes utilisées à des fins différentes.
     *
     * @return array{label: string, color: string}
     */
    public function getPerformanceColorBand(float $average): array
    {
        return match (true) {
            $average >= 16 => ['label' => 'Très Bien / Excellent', 'color' => '#2563eb'],
            $average >= 14 => ['label' => 'Bien', 'color' => '#16a34a'],
            $average >= 12 => ['label' => 'Assez Bien', 'color' => '#ca8a04'],
            $average >= 10 => ['label' => 'Passable', 'color' => '#ea580c'],
            default => ['label' => 'Insuffisant', 'color' => '#dc2626'],
        };
    }

    /**
     * Déterminer la mention selon la moyenne
     */
    public function getMention(float $average): string
    {
        return match (true) {
            $average >= 16 => 'Excellent',
            $average >= 14 => 'Très Bien',
            $average >= 12 => 'Bien',
            $average >= 10 => 'Assez Bien',
            $average >= 8 => 'Passable',
            default => 'Insuffisant',
        };
    }

    /**
     * Obtenir toutes les données pour le bulletin d'un élève
     */
    public function getBulletinData(User $student, string $period): array
    {
        $registration = $student->latestRegistration;
        $classroom = $registration?->classroom;

        if (!$classroom) {
            throw new \Exception('L\'élève n\'est pas inscrit dans une classe.');
        }

        $averageData = $this->computeAverageData($student, $classroom, $period, $registration->school_year_id);
        $rank = $this->calculateClassRank($student, $classroom, $period, $registration->school_year_id);
        $mention = $this->getMention($averageData['general_average']);

        return [
            'student' => $student,
            'classroom' => $classroom,
            'period' => $period,
            'subjects' => $averageData['subjects'],
            'general_average' => $averageData['general_average'],
            'rank' => $rank,
            'mention' => $mention,
            'total_coefficients' => $averageData['total_coefficients'],
        ];
    }

    /**
     * Obtenir les données pour tous les bulletins d'une classe
     */
    public function getClassBulletins(Classroom $classroom, string $period): array
    {
        $students = User::role('eleve')
            ->whereHas('registrations', function ($query) use ($classroom) {
                $query->where('classroom_id', $classroom->id)
                    ->where('status', 'active');
            })
            ->get();

        $bulletins = [];

        foreach ($students as $student) {
            $bulletins[] = $this->getBulletinData($student, $period);
        }

        // Trier par moyenne générale décroissante
        usort($bulletins, function ($a, $b) {
            return $b['general_average'] <=> $a['general_average'];
        });

        return $bulletins;
    }
}
