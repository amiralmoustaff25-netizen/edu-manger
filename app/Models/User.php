<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ParentModel; 

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes; 
    /**
     * 
     * Un utilisateur (élève) peut avoir plusieurs inscriptions (une par année scolaire).
     */
    public function registrations(): \Illuminate\Database\Eloquent\Relations\HasMany
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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'matricule',
        'name',
        'email',
        'password',
        'role',
        'cycle',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_notes',
        'allergies',
        'is_active',
        'created_by',
        'contract_started_at',
        'password_must_change',
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
}
