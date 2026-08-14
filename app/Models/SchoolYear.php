<?php

namespace App\Models;

use App\Support\SchoolYearStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolYear extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'year_string',
        'start_date',
        'end_date',
        'is_active',
        'status', // voir App\Support\SchoolYearStatus
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'closing_started_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function pedagogicalAssignments(): HasMany
    {
        return $this->hasMany(PedagogicalAssignment::class);
    }

    public function academicPeriods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class)->orderBy('position');
    }

    public function gradeSetting()
    {
        return $this->hasOne(GradeSetting::class);
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Scope pour l'année active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePreparation($query)
    {
        return $query->where('status', SchoolYearStatus::PREPARATION);
    }

    public function scopeClosing($query)
    {
        return $query->where('status', SchoolYearStatus::CLOSING);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', SchoolYearStatus::CLOSED);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', SchoolYearStatus::ARCHIVED);
    }

    /**
     * Obtenir l'année active
     */
    public static function getActive(): ?self
    {
        return static::active()->first();
    }

    /**
     * Fait transitionner l'année vers un nouveau statut du cycle de vie, en validant que la
     * transition est autorisée (voir App\Support\SchoolYearStatus::TRANSITIONS). Centralise
     * ce qui était auparavant dupliqué entre cette méthode, l'ancienne activate() et
     * SchoolYearController::activate().
     */
    public function transitionTo(string $status): void
    {
        $from = $this->status;

        if ($status === $from) {
            return;
        }

        if (! SchoolYearStatus::canTransition($from, $status)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transition invalide : impossible de passer de « %s » à « %s ».',
                    SchoolYearStatus::label($from),
                    SchoolYearStatus::label($status)
                ),
            ]);
        }

        DB::transaction(function () use ($status, $from) {
            if ($status === SchoolYearStatus::ACTIVE && $from === SchoolYearStatus::CLOSED) {
                // Réouverture exceptionnelle (closed -> active) : lève UNIQUEMENT le verrou
                // d'écriture (isLocked() se base sur status, pas sur is_active). Ne touche
                // jamais is_active ni l'année réellement en cours d'utilisation — rouvrir une
                // vieille année clôturée pour corriger une erreur ne doit jamais couper le
                // contexte opérationnel actuel de l'établissement (bug constaté et corrigé :
                // la première version faisait passer l'année active en cours à 'closed' comme
                // effet de bord, exactement ce qu'il ne faut jamais faire ici).
                $this->reopened_at = now();
                $this->reopened_by = auth()->id();
            } elseif ($status === SchoolYearStatus::ACTIVE) {
                // Activation normale (preparation -> active, ou annulation de clôture
                // closing -> active) : devient le contexte actif de l'établissement, ferme
                // automatiquement l'année qui l'était (pas d'assistant de clôture avec
                // vérifications pour ce cas historique de bascule directe — cf. sous-étape
                // B/C pour la vraie clôture assistée).
                static::where('id', '!=', $this->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'status' => SchoolYearStatus::CLOSED, 'closed_at' => now()]);

                $this->is_active = true;
            }

            if ($status === SchoolYearStatus::CLOSING) {
                $this->closing_started_at = now();
            }

            if ($status === SchoolYearStatus::CLOSED) {
                $this->closed_at = now();
            }

            if ($status === SchoolYearStatus::ARCHIVED) {
                $this->archived_at = now();
            }

            $this->status = $status;
            $this->save();
        });
    }

    /**
     * Activer cette année scolaire (désactive les autres). Conservé pour compatibilité avec
     * le code existant ; délègue désormais à transitionTo().
     *
     * Cas particulier : une année rouverte exceptionnellement a déjà status=ACTIVE (voir
     * transitionTo()) mais is_active=false — transitionTo() no-op sur un statut inchangé,
     * donc ce cas bascule is_active directement sans repasser par la machine à états.
     */
    public function activate(): void
    {
        if ($this->status === SchoolYearStatus::ACTIVE && ! $this->is_active) {
            DB::transaction(function () {
                static::where('id', '!=', $this->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'status' => SchoolYearStatus::CLOSED, 'closed_at' => now()]);

                $this->update(['is_active' => true]);
            });

            return;
        }

        $this->transitionTo(SchoolYearStatus::ACTIVE);
    }

    /**
     * Calculer le nombre de jours restants dans l'année
     */
    public function getRemainingDaysAttribute(): int
    {
        if (! $this->end_date) {
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
               (! $this->end_date || $this->end_date->gte(now()));
    }

    /**
     * Vrai lorsque la date de fin est dépassée mais que l'année n'a jamais été
     * transitionnée hors de 'active' — cas à signaler dans l'UI ("à clôturer"), distinct
     * d'une année réellement clôturée/archivée. Rien ne fait cette transition automatiquement.
     */
    public function isPastEndDate(): bool
    {
        return $this->status === SchoolYearStatus::ACTIVE
            && $this->end_date
            && $this->end_date->lt(now());
    }

    /**
     * Une année clôturée ou archivée est verrouillée : ses inscriptions, paiements et
     * grilles tarifaires ne doivent plus être modifiés (voir SchoolYearGuardService).
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [SchoolYearStatus::CLOSED, SchoolYearStatus::ARCHIVED], true);
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
                $schoolYear->status = $schoolYear->is_active ? SchoolYearStatus::ACTIVE : SchoolYearStatus::PREPARATION;
            }
        });
    }
}
