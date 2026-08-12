<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    use HasFactory;

    // La migration crée la table au singulier ('student_class_history') ; sans cette
    // déclaration, Eloquent cherche 'student_class_histories' (pluriel par défaut) qui
    // n'existe pas — jamais remarqué jusqu'ici puisque ce modèle n'était encore utilisé
    // nulle part dans le code.
    protected $table = 'student_class_history';

    protected $fillable = [
        'user_id',
        'classroom_id',
        'school_year_id',
        'annee_scolaire',
        'resultat',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
