<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sanction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'date_incident',
        'auteur_id',
        'mesure',
    ];

    protected $casts = [
        'date_incident' => 'date',
    ];

    public const TYPES = [
        'avertissement_verbal' => 'Avertissement verbal',
        'avertissement_ecrit' => 'Avertissement écrit',
        'retenue' => 'Retenue',
        'exclusion_temporaire' => 'Exclusion temporaire',
        'autre' => 'Autre',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
