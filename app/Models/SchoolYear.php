<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolYear extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'year_string',
        'start_date',
        'end_date',
        'is_active',
        'status', // 'upcoming', 'active', 'completed'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Scope pour l'année active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les années à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    /**
     * Scope pour les années terminées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Obtenir l'année active
     */
    public static function getActive(): ?self
    {
        return static::active()->first();
    }

    /**
     * Activer cette année scolaire (désactive les autres)
     */
    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false, 'status' => 'completed']);
        $this->update(['is_active' => true, 'status' => 'active']);
    }

    /**
     * Calculer le nombre de jours restants dans l'année
     */
    public function getRemainingDaysAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }
        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Vérifier si l'année est en cours
     */
    public function isCurrent(): bool
    {
        return $this->is_active && 
               $this->start_date && $this->start_date->lte(now()) && 
               (!$this->end_date || $this->end_date->gte(now()));
    }

    /**
     * Boot du modèle pour garantir une seule année active
     */
    protected static function booted()
    {
        static::updating(function ($schoolYear) {
            if ($schoolYear->isDirty('is_active') && $schoolYear->is_active) {
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });

        static::creating(function ($schoolYear) {
            if ($schoolYear->is_active) {
                static::where('is_active', true)->update(['is_active' => false]);
            }
            
            // Définir automatiquement le statut si non fourni
            if (empty($schoolYear->status)) {
                $schoolYear->status = $schoolYear->is_active ? 'active' : 'upcoming';
            }
        });
    }
}
