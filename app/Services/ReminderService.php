<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\Registration;
use Carbon\Carbon;

class ReminderService
{
    /**
     * Générer des rappels automatiques pour les paiements en retard
     */
    public function generateOverdueReminders(): void
    {
        $registrations = Registration::where('status', 'active')
            ->with(['user', 'classroom'])
            ->get();

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $currentMonthIndex = Carbon::now()->month - 1;

        foreach ($registrations as $registration) {
            // Vérifier les mois impayés
            $paidMonths = $registration->payments()
                ->where('status', 'complet')
                ->pluck('month')
                ->toArray();

            // Vérifier les 3 derniers mois
            for ($i = 0; $i < 3; $i++) {
                $monthIndex = $currentMonthIndex - $i;
                if ($monthIndex < 0) continue;

                $monthName = $months[$monthIndex];
                
                // Si le mois n'est pas payé et qu'il n'y a pas déjà un rappel
                if (!in_array($monthName, $paidMonths)) {
                    $existingReminder = Reminder::where('registration_id', $registration->id)
                        ->where('type', 'overdue')
                        ->where('status', 'pending')
                        ->whereJsonContains('metadata->month', $monthName)
                        ->exists();

                    if (!$existingReminder) {
                        $this->createReminder($registration, 'overdue', $monthName);
                    }
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
    protected function createReminder(Registration $registration, string $type, string $month, ?Carbon $scheduledAt = null): void
    {
        $scheduledAt = $scheduledAt ?? Carbon::now();
        
        $message = match($type) {
            'overdue' => "Votre paiement pour le mois de {$month} est en retard. Veuillez régulariser votre situation au plus vite.",
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
