<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $schoolName = config('app.name', 'École');
        $studentName = $this->payment->registration->user->name;
        $className = $this->payment->registration->classroom->name ?? 'Non assigné';
        
        $mail = (new MailMessage)
            ->subject("Reçu de paiement - {$schoolName}")
            ->greeting("Bonjour {$studentName},")
            ->line("Nous confirmons avoir reçu votre paiement de **" . number_format($this->payment->amount, 0) . " FCFA**.")
            ->line("**Détails du paiement :**")
            ->line("- Mois : {$this->payment->month}")
            ->line("- Date : {$this->payment->payment_date->format('d/m/Y')}")
            ->line("- Mode : {$this->payment->payment_method}")
            ->line("- Classe : {$className}")
            ->line("- Numéro de reçu : {$this->payment->receipt_number}")
            ->line("Merci pour votre confiance.")
            ->salutation("L'équipe {$schoolName}");

        // Attacher le reçu PDF si disponible
        if ($this->payment->receipt_number) {
            $pdf = \PDF::loadView('accounting.payments.receipt', ['payment' => $this->payment]);
            $mail->attachData($pdf->output(), "recu-{$this->payment->receipt_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'month' => $this->payment->month,
            'receipt_number' => $this->payment->receipt_number,
            'student_name' => $this->payment->registration->user->name,
            'message' => 'Paiement reçu avec succès',
        ];
    }
}
