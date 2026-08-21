<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('edu.notifications.mail_enabled', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->announcement->title)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->announcement->title)
            ->line(strip_tags($this->announcement->content))
            ->action('Voir la notification', route('notifications.show', ['notification' => $this->id]))
            ->salutation(config('app.name'));
    }

    protected function payload(): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'content' => $this->announcement->content,
            'type' => $this->announcement->type,
            'priority' => $this->announcement->priority,
            'category' => 'administrative',
            'attachment' => $this->announcement->attachment,
            'author_name' => $this->announcement->author?->name,
            'published_at' => $this->announcement->published_at?->toIso8601String(),
        ];
    }
}
