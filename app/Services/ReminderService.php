<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\Registration;
use Carbon\Carbon;

class ReminderService
{
    public function __construct(private FeeService $feeService)
    {
    }

    /**
     * Générer des rappels automatiques pour les paiements en retard.
     *
     * Utilise FeeService (source de vérité unique déjà utilisée par
     * AccountingController::alerts et par le tableau de bord) pour déterminer
     * les mensualités réellement impayées : montant dû, remises appliquées et
     * date d'échéance réelle de l'année scolaire. L'ancienne version comparait
     * seulement le libellé du mois aux paiements 'complet' existants, sans
     * jamais tenir compte des montants — un élève avec une remise ou un
     * paiement partiel pouvait être signalé en retard à tort, ou à l'inverse
     * ne jamais l'être, selon un algorithme différent de celui utilisé par
     * la page "Impayés & Recouvrement".
     */
    public function generateOverdueReminders(): void
    {
        $registrations = Registration::where('status', 'active')
            ->with(['user', 'classroom', 'schoolYear'])
            ->get();

        foreach ($registrations as $registration) {
            $overdueFees = collect($this->feeService->getPendingFees($registration))
                ->where('month', '!=', null)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', now()->toDateString());

            foreach ($overdueFees as $fee) {
                $existingReminder = Reminder::where('registration_id', $registration->id)
                    ->where('type', 'overdue')
                    ->where('status', 'pending')
                    ->whereJsonContains('metadata->month', $fee['month'])
                    ->exists();

                if (!$existingReminder) {
                    $this->createReminder($registration, 'overdue', $fee['month'], null, $fee['remaining_amount']);
                }
            }
        }
    }

    /**
     * Générer des rappels pour les échéances à venir
     */
    public function generateUpcomingReminders(): void
    {
        $registrations = Registration::where('status', 'active')
            ->with(['user', 'classroom'])
            ->get();

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $currentMonthIndex = Carbon::now()->month - 1;

        foreach ($registrations as $registration) {
            // Rappel pour le mois prochain
            $nextMonthIndex = ($currentMonthIndex + 1) % 12;
            $nextMonthName = $months[$nextMonthIndex];

            $existingReminder = Reminder::where('registration_id', $registration->id)
                ->where('type', 'payment_due')
                ->where('status', 'pending')
                ->whereJsonContains('metadata->month', $nextMonthName)
                ->exists();

            if (!$existingReminder) {
                $scheduledAt = Carbon::now()->addDays(7); // Rappel 7 jours avant
                $this->createReminder($registration, 'payment_due', $nextMonthName, $scheduledAt);
            }
        }
    }

    /**
     * Créer un rappel
     */
    protected function createReminder(Registration $registration, string $type, string $month, ?Carbon $scheduledAt = null, ?float $amount = null): void
    {
        $scheduledAt = $scheduledAt ?? Carbon::now();
        $amountText = $amount ? ' (' . number_format($amount, 0, ',', ' ') . ' FCFA restants)' : '';

        $message = match($type) {
            'overdue' => "Votre paiement pour le mois de {$month} est en retard{$amountText}. Veuillez régulariser votre situation au plus vite.",
            'payment_due' => "Rappel: Le paiement pour le mois de {$month} est dû prochainement.",
            default => "Rappel de paiement."
        };

        Reminder::create([
            'registration_id' => $registration->id,
            'type' => $type,
            'message' => $message,
            'scheduled_at' => $scheduledAt,
            'channel' => 'email',
            'metadata' => [
                'month' => $month,
                'amount' => $amount,
                'student_name' => $registration->user->name,
                'classroom' => $registration->classroom?->name,
            ],
        ]);
    }

    /**
     * Créer un rappel personnalisé
     */
    public function createCustomReminder(Registration $registration, string $message, Carbon $scheduledAt): Reminder
    {
        return Reminder::create([
            'registration_id' => $registration->id,
            'type' => 'custom',
            'message' => $message,
            'scheduled_at' => $scheduledAt,
            'channel' => 'email',
            'metadata' => [
                'student_name' => $registration->user->name,
                'classroom' => $registration->classroom?->name,
            ],
        ]);
    }
}
