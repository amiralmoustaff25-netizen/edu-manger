<?php

namespace App\Services;

use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\TeachingSession;

class ProgramProgressService
{
    public function metrics(ProgramAnnual $program): array
    {
        $lessons = $program->lessons()->get();
        $lessonIds = $lessons->pluck('id');
        $plannedHours = (float) $lessons->sum('volume_horaire_prevu');
        $realisedHours = $lessonIds->isEmpty()
            ? 0.0
            : (float) TeachingSession::whereIn('program_chapter_id', $lessonIds)->sum('duration_hours');
        $completedLessons = $lessons->where('status', 'terminee')->count();
        $lessonCount = $lessons->count();

        return [
            'lesson_count' => $lessonCount,
            'completed_lessons' => $completedLessons,
            'planned_hours' => $plannedHours,
            'realised_hours' => $realisedHours,
            'remaining_hours' => max(0, $plannedHours - $realisedHours),
            'pedagogical_progress' => $lessonCount > 0 ? round(($completedLessons / $lessonCount) * 100, 1) : 0.0,
            'hourly_progress' => $plannedHours > 0 ? round(($realisedHours / $plannedHours) * 100, 1) : 0.0,
        ];
    }

    public function refreshLessonHours(ProgramChapter $lesson): void
    {
        $lesson->update(['volume_horaire_realise' => $lesson->teachingSessions()->sum('duration_hours')]);
        $lesson->program->invalidateProgressCache();
    }
}
