<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramChapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_annual_id',
        'parent_id',
        'ordre',
        'type',
        'titre',
        'description',
        'volume_horaire_prevu',
        'volume_horaire_realise',
    ];

    protected $casts = [
        'volume_horaire_prevu' => 'decimal:2',
        'volume_horaire_realise' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramAnnual::class, 'program_annual_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordre');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChapterCompletion::class)->orderBy('date_traitement');
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function getTotalVolumeAttribute(): float
    {
        $children = $this->children;
        if ($children->isEmpty()) {
            return (float) $this->volume_horaire_prevu;
        }

        return $children->sum(fn ($child) => (float) $child->getTotalVolumeAttribute());
    }
}
