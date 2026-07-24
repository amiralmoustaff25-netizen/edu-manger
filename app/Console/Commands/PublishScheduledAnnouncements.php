<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Console\Command;

class PublishScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:publish-scheduled';

    protected $description = 'Publier les notifications programmées dont la date est arrivée';

    public function handle(AnnouncementService $service): int
    {
        $this->info('Recherche des notifications programmées à publier...');

        $announcements = Announcement::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($announcements->isEmpty()) {
            $this->info('Aucune notification à publier.');

            return Command::SUCCESS;
        }

        foreach ($announcements as $announcement) {
            try {
                $service->publish($announcement);
                $this->info("✓ Notification #{$announcement->id} publiée : {$announcement->title}");
            } catch (\Exception $e) {
                $this->error("✗ Erreur notification #{$announcement->id} : {$e->getMessage()}");
            }
        }

        $this->info('Traitement terminé.');

        return Command::SUCCESS;
    }
}
