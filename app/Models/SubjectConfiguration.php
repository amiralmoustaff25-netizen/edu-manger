<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectConfiguration extends Model
{
    protected $fillable = ['matiere_id', 'school_year_id', 'cycle', 'serie', 'level', 'classroom_id', 'coefficient', 'bareme', 'is_active'];

    protected $casts = ['coefficient' => 'decimal:2', 'bareme' => 'decimal:2', 'is_active' => 'boolean'];

    public function matiere(): BelongsTo { return $this->belongsTo(Matiere::class); }
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
}
