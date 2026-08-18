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
        $subjectsData = [];
        $totalCoefficients = 0.0;
        $totalWeighted = 0.0;

        foreach ($this->classroomSubjects($classroom, $schoolYearId) as $matiere) {
            $notes = Note::where('user_id', $student->id)
                ->where('matiere_id', $matiere->id)
                ->where('periode', $period)
                ->get();

            $coefficient = $this->resolveCoefficient($matiere, $classroom, $schoolYearId);
            $hasNotes = $notes->isNotEmpty();
            $average = $hasNotes ? round($notes->avg('valeur'), 2) : 0.0;
            $weightedAverage = $hasNotes ? round($average * $coefficient, 2) : 0.0;

            $subjectsData[] = [
                'matiere' => $matiere->nom,
                'coefficient' => $coefficient,
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
     * Coefficient réellement applicable pour une matière/classe/année : l'écran
     * "Configuration pédagogique" > Matières & coefficients (SubjectConfiguration)
     * est la source de vérité quand un coefficient y a été configuré pour l'année
     * scolaire de l'élève — priorité au coefficient spécifique au cycle de la classe,
     * puis au coefficient "Tous les cycles" (cycle = null) de cette même année.
     * Repli sur Matiere::coefficient (champ global, non versionné) si rien n'est
     * configuré pour cette année — comportement historique conservé pour ne pas
     * casser les bulletins d'années sans configuration dédiée.
     */
    private function resolveCoefficient(Matiere $matiere, Classroom $classroom, ?int $schoolYearId): float
    {
        if ($schoolYearId) {
            $configurations = SubjectConfiguration::where('matiere_id', $matiere->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->get();

            $match = $configurations->firstWhere('cycle', $classroom->cycle)
                ?? $configurations->firstWhere('cycle', null);

            if ($match) {
                return (float) $match->coefficient;
            }
        }

        return (float) ($matiere->coefficient ?? 1.0);
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
