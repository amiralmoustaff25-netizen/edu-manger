<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedagogicalAssignment extends Model
{
    protected $fillable = ['teacher_id', 'classroom_id', 'matiere_id', 'school_year_id', 'volume_horaire_hebdo', 'is_active', 'deactivated_at', 'deactivated_by'];

    protected $casts = ['is_active' => 'boolean', 'deactivated_at' => 'datetime'];

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function matiere(): BelongsTo { return $this->belongsTo(Matiere::class); }
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
    public function teachingSessions() { return $this->hasMany(TeachingSession::class); }
}
