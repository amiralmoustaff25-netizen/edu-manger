<?php

namespace App\Models;

use Database\Factories\MatiereFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'coefficient', 'bareme'];
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function pedagogicalAssignments()
    {
        return $this->hasMany(PedagogicalAssignment::class);
    }

    public function configurations()
    {
        return $this->hasMany(SubjectConfiguration::class);
    }

    /**
     * Matière spécialisée du primaire (ex. anglais, musique) : peut avoir son propre
     * professeur dédié en plus du professeur principal. Voir config('edu.primary_specialist_subjects')
     * et PedagogicalConfigurationController::isSpecialistSubject() (même règle, dupliquée
     * là où historiquement introduite ; centralisée ici pour la synchro classe/affectation).
     */
    public function isPrimarySpecialistSubject(): bool
    {
        return in_array(Str::of($this->nom)->lower()->ascii()->toString(), config('edu.primary_specialist_subjects'), true);
    }

    /**
     * Matières "générales" du primaire (toutes sauf les spécialisées) : celles couvertes
     * par le professeur principal d'une classe de primaire.
     */
    public static function generalSubjectIds()
    {
        return static::all()->reject(fn (Matiere $matiere) => $matiere->isPrimarySpecialistSubject())->pluck('id');
    }
}
