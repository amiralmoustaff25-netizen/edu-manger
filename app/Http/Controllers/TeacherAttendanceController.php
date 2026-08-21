<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        $date = $request->date('date') ?? today();
        $dateString = $date->toDateString();
        $activeYear = SchoolYear::where('is_active', true)->first();

        $teachers = Teacher::with('user')->get()->sortBy('user.name');

        $attendances = TeacherAttendance::whereDate('date', $dateString)
            ->with('teacher.user')
            ->get()
            ->keyBy('teacher_id');

        $taughtSessions = TeachingSession::whereDate('taught_on', $dateString)
            ->with('assignment.teacher.user', 'assignment.classroom', 'assignment.matiere')
            ->get()
            ->groupBy('assignment.teacher_id');

        $todaysAssignments = PedagogicalAssignment::with(['teacher.user', 'classroom', 'matiere'])
            ->when($activeYear, fn ($query) => $query->where('school_year_id', $activeYear->id))
            ->where('is_active', true)
            ->get();

        $classesWithoutTeacher = Classroom::with('schoolYear')
            ->when($activeYear, fn ($query) => $query->where('school_year_id', $activeYear->id))
            ->orderBy('name')
            ->get()
            ->filter(function ($classroom) use ($dateString, $todaysAssignments) {
                $hasTeacher = $todaysAssignments->contains(fn ($assignment) => $assignment->classroom_id === $classroom->id);
                $teacherPresent = TeacherAttendance::whereIn('teacher_id', $todaysAssignments->where('classroom_id', $classroom->id)->pluck('teacher_id'))
                    ->where('date', $dateString)
                    ->whereIn('status', ['present', 'late'])
                    ->exists();

                return $hasTeacher && ! $teacherPresent;
            });

        return view('teacher-attendances.index', compact(
            'date',
            'dateString',
            'teachers',
            'attendances',
            'taughtSessions',
            'classesWithoutTeacher',
            'todaysAssignments'
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('create', TeacherAttendance::class);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent,excused',
            'arrival_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $validated['teacher_id'],
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'arrival_time' => $validated['arrival_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]
        );

        return redirect()->route('teacher-attendances.index', ['date' => $validated['date']])
            ->with('success', 'Pointage enregistré.');
    }
}
