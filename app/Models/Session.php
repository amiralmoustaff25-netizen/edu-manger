<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle en lecture (essentiellement) sur la table `sessions` gérée nativement
 * par le driver de session 'database' de Laravel — pas une table applicative
 * classique : `id` est l'identifiant de session (string, non auto-incrémenté),
 * `payload` est le contenu sérialisé/chiffré de la session, `last_activity`
 * un timestamp Unix brut (pas un datetime SQL).
 */
class Session extends Model
{
    protected $table = 'sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLastActivityAtAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->last_activity);
    }

    /**
     * Une session sans activité récente n'est pas forcément expirée (voir
     * SESSION_LIFETIME), mais mérite un signalement visuel distinct dans
     * l'écran "sessions actives" — seuil arbitraire plus court que la durée
     * de vie totale de la session, pour repérer les onglets laissés ouverts.
     */
    public function getIsInactiveAttribute(): bool
    {
        return $this->last_activity < now()->subMinutes(15)->timestamp;
    }
}
