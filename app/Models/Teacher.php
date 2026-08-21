<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TeacherFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

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

    /**
     * Le RIB doit rester utilisable pour un virement de salaire — bcrypt() (hachage à sens
     * unique) le rendait irrémédiablement irrécupérable, y compris pour ce besoin métier
     * légitime. Chiffrement réversible à la place ; la vue ne l'affiche jamais en clair
     * (resources/views/teachers/show.blade.php), donc la confidentialité à l'écran est
     * inchangée.
     */
    public function setRibAttribute(?string $value): void
    {
        $this->attributes['rib'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRibAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Valeur enregistrée avant ce correctif (bcrypt, irréversible) : impossible à
            // déchiffrer. On renvoie la valeur brute plutôt que null, pour ne pas faire
            // croire à tort qu'aucun RIB n'est enregistré.
            return $value;
        }
    }

    /**
     * Le compte utilisateur et la fiche enseignant d'un même professeur partagent
     * toujours ce matricule (voir TeacherController::store() et
     * UserController::storeProfesseur()) — vérifier aussi contre `users` évite de
     * régénérer un matricule déjà pris par un compte si les deux tables venaient à
     * nouveau à diverger (ex. suppression manuelle d'une seule des deux lignes).
     */
    public static function generateMatricule(): string
    {
        $year = date('y');
        $sequence = self::withTrashed()->where('matricule', 'like', "PROF-{$year}-%")->count() + 1;

        do {
            $matricule = 'PROF-'.$year.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (
            self::withTrashed()->where('matricule', $matricule)->exists()
            || User::withTrashed()->where('matricule', $matricule)->exists()
        );

        return $matricule;
    }

    protected static function newFactory()
    {
        return TeacherFactory::new();
    }
}
