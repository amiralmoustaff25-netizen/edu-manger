<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sanction extends Model
{
    protected \ = [
        'user_id',
        'type',
        'description',
        'date_incident',
        'auteur_id',
        'mesure',
    ];

    protected \ = [
        'date_incident' => 'date',
    ];

    public const TYPES = [
        'avertissement_verbal' => 'Avertissement verbal',
        'avertissement_ecrit' => 'Avertissement ecrit',
        'retenue' => 'Retenue',
        'exclusion_temporaire' => 'Exclusion temporaire',
        'autre' => 'Autre',
    ];

    public function student(): BelongsTo
    {
        return \->belongsTo(User::class, 'user_id');
    }

    public function author(): BelongsTo
    {
        return \->belongsTo(User::class, 'auteur_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[\->type] ?? ucfirst((string) \->type);
    }
}
