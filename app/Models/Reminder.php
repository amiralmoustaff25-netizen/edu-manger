<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'registration_id',
        'payment_id',
        'type',
        'message',
        'scheduled_at',
        'sent_at',
        'status',
        'channel',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relation avec l'inscription
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Relation avec le paiement
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Marquer le rappel comme envoyé
     */
    public function markAsSent()
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    /**
     * Marquer le rappel comme échoué
     */
    public function markAsFailed()
    {
        $this->status = 'failed';
        $this->save();
    }

    /**
     * Scope pour les rappels en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope pour les rappels d'un type spécifique
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
