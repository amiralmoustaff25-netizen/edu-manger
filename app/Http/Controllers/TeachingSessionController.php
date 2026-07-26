<?php

namespace App\Http\Controllers;

use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\TeachingSession;
use Illuminate\Http\Request;

class TeachingSessionController extends Controller
{
    public function index()
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $schoolYear = SchoolYear::getActive();
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $assignments = PedagogicalAssignment::with(['classroom', 'matiere'])
            ->where('teacher_id', $teacher->id)
            ->where('school_year_id', $schoolYear?->id)
            ->where('is_active', true)
            ->withSum(['teachingSessions as completed_hours' => fn ($query) => $query->whereBetween('taught_on', [$weekStart, $weekEnd])], 'duration_hours')
            ->orderBy('classroom_id')
            ->get()
            ->each(function ($assignment) {
                $assignment->completed_hours = (float) ($assignment->completed_hours ?? 0);
                $assignment->remaining_hours = max(0, (float) $assignment->volume_horaire_hebdo - $assignment->completed_hours);
                $assignment->progress = $assignment->volume_horaire_hebdo > 0 ? min(100, round($assignment->completed_hours / $assignment->volume_horaire_hebdo * 100)) : 0;
            });
        $sessions = TeachingSession::with('assignment.classroom', 'assignment.matiere')->whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacher->id))->latest('taught_on')->paginate(12);

        return view('teachers.teaching-sessions.index', compact('assignments', 'sessions', 'weekStart', 'weekEnd'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['pedagogical_assignment_id' => ['required', 'exists:pedagogical_assignments,id'], 'taught_on' => ['required', 'date'], 'duration_hours' => ['required', 'numeric', 'min:0.25', 'max:12'], 'summary' => ['nullable', 'string', 'max:1000']]);
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $assignment = PedagogicalAssignment::whereKey($data['pedagogical_assignment_id'])->where('teacher_id', $teacher->id)->where('is_active', true)->firstOrFail();
        TeachingSession::create([...$data, 'recorded_by' => auth()->id()]);

        return redirect()->route('professeur.teaching-sessions.index')->with('success', "Séance pointée pour {$assignment->classroom->name}.");
    }
}
