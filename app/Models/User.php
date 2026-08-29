<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /**
     * Cache des noms de permissions révoquées pour la requête en cours.
     */
    protected ?Collection $revokedPermissionNamesCache = null;

    /**
     * Cache des permissions effectives pour la requête en cours.
     */
    protected ?Collection $effectivePermissionsCache = null;

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes {
        HasRoles::hasPermissionTo as private originalHasPermissionTo;
    }

    /**
     * La colonne 'email' porte une contrainte UNIQUE en base : sans ceci, un compte
     * archivé (soft delete) continuerait à occuper son adresse indéfiniment, bloquant
     * toute réutilisation par un nouveau compte. On la libère en la préfixant de façon
     * réversible à l'archivage, et on la restitue à la restauration. Ceci vit au niveau
     * du modèle (et non d'un seul contrôleur) car plusieurs chemins soft-suppriment un
     * User (UserController::destroy(), ProfileController::destroy() en auto-suppression).
     *
     * Fait à part de SoftDeletes::runSoftDelete() (qui ne persiste que deleted_at/
     * updated_at via une requête ciblée) : la mutation d'attribut seule ne suffit pas
     * côté suppression, d'où la requête explicite ci-dessous. Côté restauration,
     * restore() appelle save() : muter l'attribut suffit.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->isForceDeleting() || ! $user->email) {
                return;
            }

            $prefix = "archived-{$user->id}-";

            if (! str_starts_with($user->email, $prefix)) {
                $archivedEmail = $prefix.$user->email;
                $user->newQueryWithoutScopes()->whereKey($user->id)->update(['email' => $archivedEmail]);
                $user->setAttribute('email', $archivedEmail);
                $user->syncOriginalAttribute('email');
            }
        });

        static::restoring(function (User $user) {
            $prefix = "archived-{$user->id}-";

            if ($user->email && str_starts_with($user->email, $prefix)) {
                $user->setAttribute('email', substr($user->email, strlen($prefix)));
            }
        });
    }

    /**
     * Un professeur ne doit consulter/télécharger que les données d'un élève d'une
     * classe qui lui est assignée. La permission 'voir-detail-eleve' est nécessaire
     * pour franchir le Gate mais reste globale (Spatie court-circuite Gate::before
     * dès que l'acteur la possède, sans jamais consulter UserPolicy) : ce contrôle
     * par instance doit donc être appliqué explicitement par chaque contrôleur
     * concerné (StudentController::show(), StudentDocumentController::download()).
     */
    public function isTeacherAssignedToStudent(User $student): bool
    {
        if (! $this->hasRole('professeur') || $this->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        $teacher = $this->teacher;
        $classroomId = optional($student->latestRegistration)->classroom_id;

        return (bool) ($teacher && $classroomId && $teacher->classrooms()->where('classrooms.id', $classroomId)->exists());
    }

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
        'security_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
        'date_naissance' => 'date',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Il n'existe pas de colonne 'nom' distincte : 'name' est toujours enregistré
     * comme trim(nom.' '.prenom) (voir UserController/TeacherController/StudentEnrollmentService).
     * On retrouve donc 'nom' en retirant le suffixe ' '.prenom de 'name', et non en
     * prenant le premier mot de 'name' (qui tronquerait un nom de famille composé).
     */
    /**
     * L'email d'un compte archivé est préfixé "archived-{id}-" pour libérer
     * l'adresse (contrainte UNIQUE en base) tout en la gardant récupérable pour
     * une restauration (voir UserController::destroy()/restore()). Cet accesseur
     * retire ce préfixe pour l'affichage, sans toucher à la valeur stockée.
     */
    public function getDisplayEmailAttribute(): ?string
    {
        $email = $this->attributes['email'] ?? null;
        $prefix = "archived-{$this->id}-";

        if ($email && str_starts_with($email, $prefix)) {
            return substr($email, strlen($prefix));
        }

        return $email;
    }

    public function getNomAttribute(): string
    {
        $name = $this->attributes['name'] ?? '';

        if ($this->prenom && str_ends_with($name, ' '.$this->prenom)) {
            return mb_substr($name, 0, mb_strlen($name) - mb_strlen($this->prenom) - 1);
        }

        return $name;
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

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
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

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentModel::class, 'user_id');
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

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function revokedPermissionNames(): Collection
    {
        if ($this->revokedPermissionNamesCache === null) {
            $this->revokedPermissionNamesCache = $this->permissionOverrides()
                ->where('type', 'revoke')
                ->with('permission')
                ->get()
                ->pluck('permission.name')
                ->values();
        }

        return $this->revokedPermissionNamesCache;
    }

    public function directGrantedPermissionNames(): Collection
    {
        return $this->getDirectPermissions()->pluck('name')->values();
    }

    public function effectivePermissionNames(): Collection
    {
        if ($this->effectivePermissionsCache !== null) {
            return $this->effectivePermissionsCache;
        }

        if ($this->hasRole('super-admin')) {
            return $this->effectivePermissionsCache = Permission::all()->pluck('name')->values();
        }

        $revoked = $this->revokedPermissionNames();

        return $this->effectivePermissionsCache = $this->getAllPermissions()
            ->pluck('name')
            ->diff($revoked)
            ->values();
    }

    public function forgetPermissionCache(): void
    {
        $this->effectivePermissionsCache = null;
        $this->revokedPermissionNamesCache = null;
    }

    public function refresh(): self
    {
        $this->forgetPermissionCache();

        return parent::refresh();
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $name = is_string($permission) ? $permission : $permission?->name;

        if ($name && $this->revokedPermissionNames()->contains($name)) {
            return false;
        }

        try {
            return $this->originalHasPermissionTo($permission, $guardName);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    // Dans app/Models/User.php

    /**
     * Met à jour la colonne legacy `role` depuis le premier rôle Spatie actif.
     * Cette colonne est en cours de migration vers Spatie ; l'assignation via
     * forceFill est protégée car elle n'est pas exposée au mass assignment.
     */
    public function syncPrimaryRoleColumn(): void
    {
        // Si le rôle déjà stocké fait toujours partie des rôles actuels, on le
        // conserve tel quel plutôt que de le redéterminer via $this->roles->first() :
        // model_has_roles est une table pivot sans colonne id/timestamp, l'ordre de
        // lecture n'est donc garanti par aucun moteur de BDD — MySQL et SQLite
        // pouvaient renvoyer un ordre différent pour les mêmes données, rendant le
        // rôle "principal" choisi non déterministe (vu en CI, jamais en local SQLite).
        $roleNames = $this->roles->pluck('name');

        if ($this->role && $roleNames->contains($this->role)) {
            return;
        }

        $primary = $roleNames->first();

        if ($primary) {
            $this->forceFill(['role' => $primary])->save();
        }
    }

    public static function generateMatricule(string $role): string
    {
        $prefix = match ($role) {
            'super-admin' => 'SAD',
            'admin' => 'ADM',
            'manager-comptable' => 'MCO',
            'comptable' => 'CPT',
            'surveillant' => 'SURV',
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
        // SEC-03 : la photo est stockée sur le disque privé `local` et servie
        // via une route contrôlée (StudentController::photo()) qui revérifie
        // la permission d'accès au dossier de l'élève, plutôt que par une URL
        // publique directe.
        if ($this->profile_photo_path && $this->exists) {
            return route('students.photo', $this->getKey());
        }

        // Default avatar: initials
        $initials = collect(explode(' ', $this->name))
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join('');

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=FFFFFF&background=4F46E5';
    }
}
