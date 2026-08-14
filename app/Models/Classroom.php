<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Teacher;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cycle',
        'school_year_id',
        'teacher_id',
        'max_students',
        'promotes_to_classroom_id',
    ];

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec l'enseignant titulaire
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relation avec les notes
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_classroom')
            ->withPivot('annee_scolaire', 'matiere_id', 'volume_horaire_hebdo')
            ->withTimestamps();
    }

    /**
     * Relation avec les inscriptions
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function pedagogicalAssignments(): HasMany
    {
        return $this->hasMany(PedagogicalAssignment::class);
    }

    /**
     * Classe vers laquelle les élèves de cette classe sont promus l'année
     * suivante (ex. CM1 A -> CM2 A). Voir PromotionController.
     */
    public function promotesTo(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'promotes_to_classroom_id');
    }

    /**
     * Classes qui promeuvent explicitement vers celle-ci.
     */
    public function promotedFrom(): HasMany
    {
        return $this->hasMany(Classroom::class, 'promotes_to_classroom_id');
    }
}
