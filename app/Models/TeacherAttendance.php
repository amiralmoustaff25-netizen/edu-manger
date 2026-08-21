<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'date',
        'status',
        'arrival_time',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_time' => 'datetime:H:i',
    ];

    public const STATUSES = [
        'present' => 'Présent',
        'absent' => 'Absent',
        'late' => 'Retard',
        'excused' => 'Excusé',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
