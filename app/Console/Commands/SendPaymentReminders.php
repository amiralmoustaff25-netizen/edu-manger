<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Models\Registration;
use App\Notifications\PaymentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer les rappels de paiement automatiques';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recherche des rappels à envoyer...');
        
        $reminders = Reminder::pending()->get();
        
        if ($reminders->isEmpty()) {
            $this->info('Aucun rappel à envoyer.');
            return Command::SUCCESS;
        }

        $this->info("{$reminders->count()} rappel(s) trouvé(s).");

        foreach ($reminders as $reminder) {
            try {
                $registration = $reminder->registration;
                
                if (!$registration || !$registration->user) {
                    $this->warn("Rappel #{$reminder->id}: Inscription ou utilisateur introuvable.");
                    $reminder->markAsFailed();
                    continue;
                }

                // Envoyer la notification
                $registration->user->notify(new PaymentReminder($reminder));
                
                // Marquer comme envoyé
                $reminder->markAsSent();
                
                $this->info("✓ Rappel #{$reminder->id} envoyé à {$registration->user->name}");
                
            } catch (\Exception $e) {
                $this->error("✗ Erreur lors de l'envoi du rappel #{$reminder->id}: {$e->getMessage()}");
                $reminder->markAsFailed();
                Log::error('Reminder sending failed', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Tous les rappels ont été traités.');
        return Command::SUCCESS;
    }
}
