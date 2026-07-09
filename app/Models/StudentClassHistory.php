<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    protected \ = [
        'user_id',
        'classroom_id',
        'school_year_id',
        'annee_scolaire',
        'resultat',
    ];

    public function student(): BelongsTo
    {
        return \->belongsTo(User::class, 'user_id');
    }

    public function classroom(): BelongsTo
    {
        return \->belongsTo(Classroom::class);
    }

    public function schoolYear(): BelongsTo
    {
        return \->belongsTo(SchoolYear::class);
    }
}
