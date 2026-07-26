<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSetting extends Model
{
    protected $fillable = ['school_year_id', 'organization_mode', 'default_scale', 'minimum_grade', 'allow_decimals', 'decimal_places', 'allow_appreciations', 'allow_edit_after_submission', 'administrative_validation_required', 'lock_after_validation'];

    protected $casts = ['minimum_grade' => 'decimal:2', 'allow_decimals' => 'boolean', 'allow_appreciations' => 'boolean', 'allow_edit_after_submission' => 'boolean', 'administrative_validation_required' => 'boolean', 'lock_after_validation' => 'boolean'];

    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
}
