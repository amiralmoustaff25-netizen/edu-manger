<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\User;

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

        $total = $notes->sum('valeur');
        $count = $notes->count();

        return round($total / $count, 2);
    }

    /**
     * Calculer la moyenne pondérée d'un élève pour une période
     */
    public function calculateWeightedAverage(User $student, string $period): float
    {
        $notes = Note::where('user_id', $student->id)
            ->where('periode', $period)
            ->with('matiere')
            ->get();

        if ($notes->isEmpty()) {
            return 0.0;
        }

        $totalWeighted = 0;
        $totalCoefficients = 0;

        foreach ($notes as $note) {
            $coefficient = $note->matiere->coefficient ?? 1.0;
            $totalWeighted += $note->valeur * $coefficient;
            $totalCoefficients += $coefficient;
        }

        if ($totalCoefficients === 0) {
            return 0.0;
        }

        return round($totalWeighted / $totalCoefficients, 2);
    }

    /**
     * Calculer le classement d'un élève dans sa classe pour une période
     */
    public function calculateClassRank(User $student, Classroom $classroom, string $period): int
    {
        $studentAverage = $this->calculateWeightedAverage($student, $period);

        $students = User::role('eleve')
            ->whereHas('registrations', function ($query) use ($classroom) {
                $query->where('classroom_id', $classroom->id)
                    ->where('status', 'active');
            })
            ->get();

        $averages = $students->map(function ($s) use ($period) {
            return $this->calculateWeightedAverage($s, $period);
        });

        // Compteur des élèves avec une moyenne strictement supérieure
        $betterCount = $averages->filter(function ($average) use ($studentAverage) {
            return $average > $studentAverage;
        })->count();

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

        // Récupérer toutes les matières de la classe
        $matieres = Matiere::all();

        $subjectsData = [];
        $totalCoefficients = 0;
        $totalWeighted = 0;

        foreach ($matieres as $matiere) {
            $notes = Note::where('user_id', $student->id)
                ->where('matiere_id', $matiere->id)
                ->where('periode', $period)
                ->get();

            $average = $notes->isEmpty() ? 0 : round($notes->avg('valeur'), 2);
            $coefficient = $matiere->coefficient ?? 1.0;
            $weightedAverage = $average * $coefficient;

            $subjectsData[] = [
                'matiere' => $matiere->nom,
                'coefficient' => $coefficient,
                'notes' => $notes->pluck('valeur')->toArray(),
                'average' => $average,
                'weighted_average' => $weightedAverage,
                'appreciation' => $notes->last()?->appreciation ?? '',
            ];

            $totalCoefficients += $coefficient;
            $totalWeighted += $weightedAverage;
        }

        $generalAverage = $totalCoefficients > 0 ? round($totalWeighted / $totalCoefficients, 2) : 0;
        $rank = $this->calculateClassRank($student, $classroom, $period);
        $mention = $this->getMention($generalAverage);

        return [
            'student' => $student,
            'classroom' => $classroom,
            'period' => $period,
            'subjects' => $subjectsData,
            'general_average' => $generalAverage,
            'rank' => $rank,
            'mention' => $mention,
            'total_coefficients' => $totalCoefficients,
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
