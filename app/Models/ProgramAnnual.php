<?php

namespace App\Models;

use App\Support\ProgramStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProgramAnnual extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'subject_id',
        'teacher_id',
        'school_year_id',
        'status',
        'submitted_at',
        'validated_by_surveillant_id',
        'validated_by_directeur_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(ProgramChapter::class)->orderBy('ordre');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ProgramHistory::class)->latest('created_at');
    }

    public function scopeForTeacher($query, $userId)
    {
        return $query->where('teacher_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForSchoolYear($query, $id)
    {
        return $query->where('school_year_id', $id);
    }

    public function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getProgressPercentage()
        );
    }

    public function getProgressPercentage(): float
    {
        $cacheKey = "program.{$this->id}.progress";

        return Cache::remember($cacheKey, 3600, function () {
            $totalVolume = (float) $this->chapters()->sum('volume_horaire_prevu');
            if ($totalVolume <= 0) {
                return 0.0;
            }

            $realised = (float) $this->chapters()->sum('volume_horaire_realise');

            return round(($realised / $totalVolume) * 100, 2);
        });
    }

    public function invalidateProgressCache(): void
    {
        Cache::forget("program.{$this->id}.progress");
    }

    public function isModifiable(): bool
    {
        return in_array($this->status, ['brouillon', 'soumis', 'rejete'], true);
    }

    public function canTransitionTo(string $status): bool
    {
        return ProgramStatus::canTransition($this->status, $status);
    }

    public function isDelayed(): bool
    {
        $last = $this->history()->where('action', 'saisie')->latest('created_at')->first();
        $date = $last?->created_at ?? $this->updated_at;

        return $date->lt(now()->subDays(30));
    }

    public function getLastCompletionDateAttribute()
    {
        return $this->chapters()->with('completions')->get()->flatMap->completions->max('date_traitement');
    }
}
