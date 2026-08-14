<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    use HasFactory;

    // La migration crée 'student_class_history' (singulier) ; le nom de table deviné
    // par Eloquent à partir du nom de classe serait 'student_class_histories'
    // (pluriel de "history" = "histories") — sans ceci, aucune requête n'aboutit.
    // Bug trouvé indépendamment des deux côtés de cette fusion, jamais remarqué avant
    // que ce modèle soit enfin réellement utilisé (assistant de promotion).
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
