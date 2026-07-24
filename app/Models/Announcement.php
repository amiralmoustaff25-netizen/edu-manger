<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'target_mode',
        'target_roles',
        'classroom_id',
        'target_user_ids',
        'attachment',
        'published_at',
        'expires_at',
        'archived_at',
        'status',
        'created_by',
        'recipient_count',
        'read_count',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereNull('archived_at');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null && $this->published_at <= now();
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived' || $this->archived_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at < now();
    }

    public function markAsPublished(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function markAsScheduled(): void
    {
        $this->update(['status' => 'scheduled']);
    }

    public function markAsArchived(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function displayTypeLabel(): string
    {
        return match ($this->type) {
            'information' => 'Information',
            'important' => 'Important',
            'urgent' => 'Urgent',
            'reminder' => 'Rappel',
            'announcement' => 'Annonce',
            default => ucfirst($this->type),
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'normal' => 'Normale',
            'important' => 'Importante',
            'urgent' => 'Urgente',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Count how many notification rows were created for this announcement.
     */
    public function notificationsRecipientCount(): int
    {
        return \Illuminate\Support\Facades\DB::table('notifications')
            ->where('type', \App\Notifications\AnnouncementPublished::class)
            ->whereJsonContains('data->announcement_id', $this->id)
            ->count();
    }

    /**
     * Count how many notification rows for this announcement are marked as read.
     */
    public function notificationsReadCount(): int
    {
        return \Illuminate\Support\Facades\DB::table('notifications')
            ->where('type', \App\Notifications\AnnouncementPublished::class)
            ->whereJsonContains('data->announcement_id', $this->id)
            ->whereNotNull('read_at')
            ->count();
    }
}
