<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cycle',
        'school_year_id',
        'teacher_id', // <-- Ajouté pour pouvoir assigner un enseignant à la classe
    ];

    /**
     * Obtenir l'enseignant titulaire de la classe.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Obtenir les notes associées à la classe.
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    /**
     * Obtenir les inscriptions (élèves) associées à cette classe.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
}