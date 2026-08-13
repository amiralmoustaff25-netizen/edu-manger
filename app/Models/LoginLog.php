<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device_type',
        'login_at',
        'logout_at',
        'status',
        'email',
        'matricule',
        'role',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    /**
     * Durée de session en secondes, ou null si la session est toujours ouverte
     * (pas encore de logout_at) ou si le login a échoué (pas de login_at exploitable).
     */
    public function getDurationInSecondsAttribute(): ?int
    {
        if (! $this->login_at || ! $this->logout_at) {
            return null;
        }

        return $this->logout_at->diffInSeconds($this->login_at);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer les connexions réussies
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope pour filtrer les tentatives échouées
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
