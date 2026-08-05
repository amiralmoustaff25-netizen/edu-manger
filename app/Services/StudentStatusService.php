<?php

namespace App\Services;

use App\Models\Registration;
use App\Support\StudentStatus;
use Illuminate\Validation\ValidationException;

/**
 * Centralise la transition de statut d'une inscription élève : valide la transition,
 * exige un motif pour les statuts sensibles, synchronise l'activation du compte
 * utilisateur et journalise l'action pour audit.
 */
class StudentStatusService
{
    public function __construct(private AuditLogService $auditLog) {}

    public function transition(Registration $registration, string $newStatus, ?string $reason = null): void
    {
        $currentStatus = $registration->status;

        if ($currentStatus === $newStatus) {
            return;
        }

        if (! StudentStatus::canTransition($currentStatus, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transition invalide : impossible de passer de « %s » à « %s ».',
                    StudentStatus::label($currentStatus),
                    StudentStatus::label($newStatus)
                ),
            ]);
        }

        if (StudentStatus::requiresReason($newStatus) && blank($reason)) {
            throw ValidationException::withMessages([
                'status_reason' => 'Un motif est obligatoire pour ce changement de statut.',
            ]);
        }

        $oldValues = $registration->only(['status', 'status_reason']);

        $registration->update([
            'status' => $newStatus,
            'status_reason' => $reason,
        ]);

        $registration->user?->update(['is_active' => $newStatus === StudentStatus::ACTIVE]);

        $this->auditLog->log(
            'student_status_changed',
            Registration::class,
            $registration->id,
            $oldValues,
            $registration->only(['status', 'status_reason']),
            sprintf('Statut de l\'élève changé : %s → %s', $currentStatus, $newStatus)
        );
    }
}
