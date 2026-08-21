<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingSession extends Model
{
    protected $fillable = ['pedagogical_assignment_id', 'program_chapter_id', 'taught_on', 'duration_hours', 'summary', 'recorded_by'];

    protected $casts = ['taught_on' => 'date', 'duration_hours' => 'decimal:2'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PedagogicalAssignment::class, 'pedagogical_assignment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ProgramChapter::class, 'program_chapter_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
