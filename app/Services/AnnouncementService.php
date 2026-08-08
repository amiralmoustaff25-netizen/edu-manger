<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class AnnouncementService
{
    /**
     * Determine the list of users targeted by an announcement.
     */
    public function resolveRecipients(Announcement $announcement): Collection
    {
        return match ($announcement->target_mode) {
            'users' => $this->resolveSpecificUsers($announcement),
            'roles' => $this->resolveByRoles($announcement->target_roles),
            'classroom' => $this->resolveByClassroom($announcement),
            default => User::where('is_active', true)->get(),
        };
    }

    /**
     * Resolve recipients when target_mode is 'users'.
     */
    protected function resolveSpecificUsers(Announcement $announcement): Collection
    {
        $ids = array_filter($announcement->target_user_ids ?? []);

        if (empty($ids)) {
            return new Collection();
        }

        return User::whereIn('id', $ids)->where('is_active', true)->get();
    }

    /**
     * Resolve recipients when target_mode is 'roles'.
     */
    protected function resolveByRoles(?array $roles): Collection
    {
        $roles = array_filter($roles ?? []);

        if (empty($roles)) {
            return new Collection();
        }

        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($roles) {
                $query->whereIn('role', $roles)
                    ->orWhereHas('roles', function ($q) use ($roles) {
                        $q->whereIn('name', $roles);
                    });
            })
            ->get();
    }

    /**
     * Resolve recipients when target_mode is 'classroom'.
     */
    protected function resolveByClassroom(Announcement $announcement): Collection
    {
        $roles = array_filter($announcement->target_roles ?? []);
        $classroom = $announcement->classroom;

        if (! $classroom) {
            return new Collection();
        }

        $recipients = new Collection();

        $roleSet = empty($roles) ? ['eleve', 'professeur', 'parent'] : $roles;

        if (in_array('eleve', $roleSet)) {
            $recipients = $recipients->merge(
                User::where('is_active', true)
                    ->whereHas('registrations', function ($q) use ($classroom) {
                        $q->where('classroom_id', $classroom->id)
                            ->where('status', 'active');
                    })
                    ->get()
            );
        }

        if (in_array('professeur', $roleSet)) {
            $recipients = $recipients->merge(
                User::where('is_active', true)
                    ->whereHas('teacher.classrooms', function ($q) use ($classroom) {
                        $q->where('classrooms.id', $classroom->id);
                    })
                    ->get()
            );
        }

        if (in_array('parent', $roleSet)) {
            $parentUsers = ParentModel::whereHas('students', function ($q) use ($classroom) {
                $q->whereHas('registrations', function ($sq) use ($classroom) {
                    $sq->where('classroom_id', $classroom->id)
                        ->where('status', 'active');
                });
            })
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();

            $recipients = $recipients->merge($parentUsers);
        }

        // Include additional selected roles that are not tied to classroom (e.g. admin, comptable)
        $otherRoles = array_diff($roleSet, ['eleve', 'professeur', 'parent']);
        if (! empty($otherRoles)) {
            $recipients = $recipients->merge($this->resolveByRoles($otherRoles));
        }

        return $recipients->unique('id')->values();
    }

    /**
     * Count recipients for an announcement without sending it.
     */
    public function countRecipients(Announcement $announcement): int
    {
        return $this->resolveRecipients($announcement)->count();
    }

    /**
     * Publish an announcement and notify recipients.
     */
    public function publish(Announcement $announcement): void
    {
        $recipients = $this->resolveRecipients($announcement);

        if ($recipients->isEmpty()) {
            $announcement->update([
                'recipient_count' => 0,
                'read_count' => 0,
                'status' => 'published',
                'published_at' => $announcement->published_at ?? now(),
            ]);

            return;
        }

        Notification::send($recipients, new AnnouncementPublished($announcement));

        $announcement->update([
            'recipient_count' => $recipients->count(),
            'read_count' => 0,
            'status' => 'published',
            'published_at' => $announcement->published_at ?? now(),
        ]);
    }

    /**
     * Schedule an announcement for future publishing.
     */
    public function schedule(Announcement $announcement, \DateTimeInterface $publishedAt): void
    {
        $announcement->update([
            'published_at' => $publishedAt,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Update read count from notification rows.
     */
    public function refreshReadCount(Announcement $announcement): void
    {
        $readCount = $announcement->notificationsReadCount();
        $recipientCount = $announcement->notificationsRecipientCount();

        $announcement->update([
            'read_count' => $readCount,
            'recipient_count' => $recipientCount,
        ]);
    }
}
