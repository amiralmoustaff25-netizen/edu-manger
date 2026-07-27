<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'matricule',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'diplomes',
        'etablissements_formation',
        'statut',
        'date_recrutement',
        'specialites',
        'filiation',
        'contact_urgence_nom',
        'contact_urgence_tel',
        'rib',
        'nombre_heures_semaine',
        'created_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_recrutement' => 'date',
        'specialites' => 'array',
        'nombre_heures_semaine' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'teacher_classroom')
            ->withPivot('annee_scolaire', 'matiere_id', 'volume_horaire_hebdo')
            ->withTimestamps();
    }

    public function pedagogicalAssignments()
    {
        return $this->hasMany(PedagogicalAssignment::class);
    }

    public function scopeFonctionnaires($query)
    {
        return $query->where('statut', 'fonctionnaire');
    }

    public function scopeContractuels($query)
    {
        return $query->where('statut', 'contractuel');
    }

    public function scopeVacataires($query)
    {
        return $query->where('statut', 'vacataire');
    }

    public function getVolumeHoraireActuelAttribute(): float
    {
        $hasNormalizedAssignments = array_key_exists('pedagogical_assignments_count', $this->attributes)
            ? $this->attributes['pedagogical_assignments_count'] > 0
            : $this->pedagogicalAssignments()->exists();

        if ($hasNormalizedAssignments) {
            if (array_key_exists('pedagogical_assignments_sum_volume_horaire_hebdo', $this->attributes)) {
                return (float) $this->attributes['pedagogical_assignments_sum_volume_horaire_hebdo'];
            }

            if ($this->relationLoaded('pedagogicalAssignments')) {
                return (float) $this->pedagogicalAssignments
                    ->where('is_active', true)
                    ->sum('volume_horaire_hebdo');
            }

            return (float) $this->pedagogicalAssignments()
                ->where('is_active', true)
                ->sum('volume_horaire_hebdo');
        }

        if ($this->relationLoaded('classrooms')) {
            return (float) $this->classrooms->sum(fn ($classroom) => $classroom->pivot->volume_horaire_hebdo);
        }

        return (float) $this->classrooms()->sum('teacher_classroom.volume_horaire_hebdo');
    }

    public function anciennete(): string
    {
        if (! $this->date_recrutement) {
            return 'Non renseignée';
        }

        $now = Carbon::now();
        $diff = $now->diff($this->date_recrutement);

        $years = $diff->y;
        $months = $diff->m;
        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' '.($years > 1 ? 'ans' : 'an');
        }

        if ($months > 0) {
            $parts[] = $months.' mois';
        }

        return $parts === [] ? 'Moins d’un mois' : implode(' et ', $parts);
    }

    public function depasseVolumeLegal(): bool
    {
        return $this->volume_horaire_actuel >= config('edu.volume_horaire_hebdomadaire_maximum', 18);
    }

    public function setRibAttribute(?string $value): void
    {
        $this->attributes['rib'] = $value ? bcrypt($value) : null;
    }

    public static function generateMatricule(): string
    {
        $year = date('y');
        $sequence = self::withTrashed()->where('matricule', 'like', "PROF-{$year}-%")->count() + 1;

        do {
            $matricule = 'PROF-'.$year.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::withTrashed()->where('matricule', $matricule)->exists());

        return $matricule;
    }

    protected static function newFactory()
    {
        return TeacherFactory::new();
    }
}
