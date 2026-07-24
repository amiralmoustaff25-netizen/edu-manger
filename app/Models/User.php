<?php

namespace App\Models;

use App\Models\Teacher;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Un utilisateur (élève) peut avoir plusieurs inscriptions (une par année scolaire).
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function latestRegistration()
    {
        return $this->hasOne(Registration::class)->latestOfMany();
    }

    public function activeRegistration()
    {
        return $this->hasOne(Registration::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Détermine si l'utilisateur est un élève (rôle colonne ou rôle Spatie).
     */
    public function isStudent(): bool
    {
        return $this->role === 'eleve' || $this->hasRole('eleve');
    }

    /**
     * Scope pour récupérer uniquement les élèves.
     */
    public function scopeStudents($query)
    {
        return $query->where('role', 'eleve')
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'eleve'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'matricule',
        'name',
        'prenom',
        'email',
        'password',
        'role',
        'cycle',
        'specialite',
        'telephone',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'adresse',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_notes',
        'allergies',
        'is_active',
        'created_by',
        'contract_started_at',
        'password_must_change',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'contract_started_at' => 'date',
        'password_must_change' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    /**
     * Relation avec les parents (pour les élèves)
     * Un élève peut avoir plusieurs parents
     */
    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_user', 'user_id', 'parent_id')
            ->withPivot('lien_parente', 'est_responsable_financier', 'est_contact_urgence')
            ->withTimestamps();
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Obtenir le responsable financier principal de l'élève
     */
    public function responsableFinancier()
    {
        return $this->parents()->wherePivot('est_responsable_financier', true)->first();
    }

    /**
     * Obtenir le contact d'urgence principal de l'élève
     */
    public function contactUrgence()
    {
        return $this->parents()->wherePivot('est_contact_urgence', true)->first();
    }
    // Dans app/Models/User.php

    public static function generateMatricule(string $role): string
    {
        $prefix = match ($role) {
            'super-admin' => 'SAD',
            'admin' => 'ADM',
            'manager-comptable' => 'MCO',
            'comptable' => 'CPT',
            'professeur' => 'PROF',
            'parent' => 'PAR',
            'eleve' => 'ELE',
            default => 'USR',
        };

        $sequence = self::withTrashed()->where('matricule', 'like', "{$prefix}-%")->count() + 1;

        do {
            $matricule = $prefix.'-'.date('y').'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::withTrashed()->where('matricule', $matricule)->exists());

        return $matricule;
    }

    /**
     * Get the user's profile photo URL.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_photo_path);
        }

        // Default avatar: initials
        $initials = collect(explode(' ', $this->name))
            ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join('');

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=FFFFFF&background=4F46E5';
    }
}
