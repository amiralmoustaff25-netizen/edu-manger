<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassroomFee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'classroom_id',
        'fee_type_id',
        'school_year_id',
        'amount',
        'version',
        'is_current',
        'previous_id',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_current' => 'boolean',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(ClassroomFee::class, 'previous_id');
    }

    public function nextVersions(): HasMany
    {
        return $this->hasMany(ClassroomFee::class, 'previous_id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
