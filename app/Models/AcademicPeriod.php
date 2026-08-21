<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPeriod extends Model
{
    protected $fillable = ['school_year_id', 'name', 'code', 'position', 'starts_at', 'ends_at', 'grade_entry_starts_at', 'grade_entry_ends_at', 'status', 'grade_entry_open'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'grade_entry_starts_at' => 'date', 'grade_entry_ends_at' => 'date', 'grade_entry_open' => 'boolean'];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
