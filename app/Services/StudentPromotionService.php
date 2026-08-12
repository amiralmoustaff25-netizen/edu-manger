<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentClassHistory;
use App\Support\StudentStatus;
use Illuminate\Support\Facades\DB;

/**
 * Assistant de promotion : propose pour chaque élève actif d'une année source une
 * décision par défaut (passage au niveau supérieur, redoublement, ou choix manuel si rien
 * ne peut être déduit), que l'admin peut corriger avant validation. L'exécution crée
 * toujours une nouvelle inscription pour l'année ACTIVE (jamais l'année source elle-même) —
 * même contrainte que StudentEnrollmentService::reenroll(), qui fait le travail réel de
 * création. Chaque décision, quelle qu'elle soit, est tracée dans StudentClassHistory
 * (jusqu'ici un schéma prêt mais jamais peuplé).
 */
class StudentPromotionService
{
    public function __construct(
        private StudentEnrollmentService $enrollmentService,
        private StudentStatusService $statusService,
    ) {}

    public function preview(SchoolYear $sourceYear): array
    {
        $activeYear = SchoolYear::getActive();

        return Registration::where('school_year_id', $sourceYear->id)
            ->where('status', StudentStatus::ACTIVE)
            ->with(['user', 'classroom'])
            ->get()
            ->map(function (Registration $registration) use ($activeYear) {
                $suggestion = $this->suggest($registration->classroom, $activeYear);

                return [
                    'registration_id' => $registration->id,
                    'student_id' => $registration->user_id,
                    'student_name' => $registration->user?->name,
                    'student_matricule' => $registration->user?->matricule,
                    'current_classroom' => $registration->classroom?->name ?? '—',
                    'suggested_action' => $suggestion['action'],
                    'suggested_classroom_id' => $suggestion['classroom_id'],
                    'suggested_classroom_name' => $suggestion['classroom_name'],
                ];
            })
            ->values()
            ->all();
    }

    private function suggest(?Classroom $classroom, ?SchoolYear $activeYear): array
    {
        if (! $classroom || ! $activeYear || $classroom->ordre === null) {
            return ['action' => 'manual', 'classroom_id' => null, 'classroom_name' => null];
        }

        $next = Classroom::where('school_year_id', $activeYear->id)
            ->where('cycle', $classroom->cycle)
            ->where('ordre', $classroom->ordre + 1)
            ->first();

        if ($next) {
            return ['action' => 'promote', 'classroom_id' => $next->id, 'classroom_name' => $next->name];
        }

        $same = Classroom::where('school_year_id', $activeYear->id)
            ->where('cycle', $classroom->cycle)
            ->where('ordre', $classroom->ordre)
            ->first();

        if ($same) {
            return ['action' => 'repeat', 'classroom_id' => $same->id, 'classroom_name' => $same->name];
        }

        return ['action' => 'manual', 'classroom_id' => null, 'classroom_name' => null];
    }

    /**
     * @param  array<int,array{action:string,classroom_id?:int,reason?:string}>  $decisions  clé = registration_id
     */
    public function apply(array $decisions): array
    {
        $results = [
            'promoted' => 0, 'repeated' => 0, 'graduated' => 0,
            'transferred' => 0, 'expelled' => 0, 'skipped' => 0, 'errors' => [],
        ];

        DB::transaction(function () use ($decisions, &$results) {
            foreach ($decisions as $registrationId => $decision) {
                $registration = Registration::with(['user', 'schoolYear'])->find($registrationId);

                if (! $registration) {
                    continue;
                }

                $action = $decision['action'] ?? 'skip';

                try {
                    match ($action) {
                        'promote' => $this->enrollInActiveYear($registration, $decision, 'admis', $results, 'promoted'),
                        'repeat' => $this->enrollInActiveYear($registration, $decision, 'redouble', $results, 'repeated'),
                        'graduate' => $this->terminate($registration, StudentStatus::GRADUATED, $decision['reason'] ?? null, $results, 'graduated'),
                        'transfer' => $this->terminate($registration, StudentStatus::TRANSFERRED, $decision['reason'] ?? null, $results, 'transferred'),
                        'expel' => $this->terminate($registration, StudentStatus::EXPELLED, $decision['reason'] ?? null, $results, 'expelled'),
                        default => $results['skipped']++,
                    };
                } catch (\Throwable $e) {
                    $results['errors'][] = ($registration->user?->name ?? "Inscription #{$registration->id}").' : '.$e->getMessage();
                }
            }
        });

        return $results;
    }

    private function enrollInActiveYear(Registration $registration, array $decision, string $resultat, array &$results, string $key): void
    {
        $activeYear = SchoolYear::getActive();
        $classroom = Classroom::find($decision['classroom_id'] ?? null);

        if (! $activeYear || ! $classroom || $classroom->school_year_id !== $activeYear->id) {
            throw new \RuntimeException("Classe cible invalide ou n'appartenant pas à l'année active.");
        }

        $this->enrollmentService->reenroll($registration->user, [
            'classroom_id' => $classroom->id,
            'is_active' => true,
        ], false);

        $this->recordHistory($registration, $resultat);
        $results[$key]++;
    }

    private function terminate(Registration $registration, string $status, ?string $reason, array &$results, string $key): void
    {
        $this->statusService->transition($registration, $status, $reason);
        $this->recordHistory($registration, $status);
        $results[$key]++;
    }

    private function recordHistory(Registration $registration, string $resultat): void
    {
        StudentClassHistory::create([
            'user_id' => $registration->user_id,
            'classroom_id' => $registration->classroom_id,
            'school_year_id' => $registration->school_year_id,
            'annee_scolaire' => $registration->schoolYear?->year_string,
            'resultat' => $resultat,
        ]);
    }
}
