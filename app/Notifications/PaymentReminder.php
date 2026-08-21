<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification
{
    protected $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
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
        $studentName = $notifiable->name;
        $message = $this->reminder->message;

        return (new MailMessage)
            ->subject("Rappel de paiement - {$schoolName}")
            ->greeting("Bonjour {$studentName},")
            ->line($message)
            ->line('Merci de votre attention.')
            ->salutation("L'équipe {$schoolName}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'type' => $this->reminder->type,
            'message' => $this->reminder->message,
            'scheduled_at' => $this->reminder->scheduled_at,
        ];
    }
}
