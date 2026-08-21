<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Effectif de chaque classe affectée, pour permettre de pointer les présences
        // directement depuis le formulaire de pointage de cours (cahier de texte).
        $rosters = $assignments->pluck('classroom_id')->unique()->mapWithKeys(function ($classroomId) {
            $students = Registration::where('classroom_id', $classroomId)
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter()
                ->values();

            return [$classroomId => $students];
        });

        return view('teachers.teaching-sessions.index', compact('assignments', 'sessions', 'weekStart', 'weekEnd', 'rosters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pedagogical_assignment_id' => ['required', 'exists:pedagogical_assignments,id'],
            'taught_on' => ['required', 'date'],
            'duration_hours' => ['required', 'numeric', 'min:0.25', 'max:12'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'attendances' => ['sometimes', 'array'],
            'attendances.*.status' => ['nullable', 'in:present,absent,late,excused'],
        ]);

        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $assignment = PedagogicalAssignment::whereKey($data['pedagogical_assignment_id'])->where('teacher_id', $teacher->id)->where('is_active', true)->firstOrFail();

        // Empêche de marquer présent/absent un utilisateur qui n'est pas réellement
        // inscrit dans cette classe (les clés du tableau attendances viennent du
        // formulaire, donc falsifiables).
        $enrolledStudentIds = Registration::where('classroom_id', $assignment->classroom_id)
            ->where('status', 'active')
            ->pluck('user_id');

        DB::transaction(function () use ($data, $teacher, $assignment, $enrolledStudentIds) {
            TeachingSession::create([
                'pedagogical_assignment_id' => $data['pedagogical_assignment_id'],
                'taught_on' => $data['taught_on'],
                'duration_hours' => $data['duration_hours'],
                'summary' => $data['summary'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            foreach ($data['attendances'] ?? [] as $userId => $attendance) {
                if (empty($attendance['status'])) {
                    continue;
                }

                abort_unless($enrolledStudentIds->contains((int) $userId), 403, 'Un ou plusieurs élèves ne sont pas inscrits dans cette classe.');

                Attendance::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'classroom_id' => $assignment->classroom_id,
                        'date' => $data['taught_on'],
                    ],
                    [
                        'status' => $attendance['status'],
                        'notes' => $attendance['notes'] ?? null,
                        // Attendance::recorded_by référence users.id (recordedBy() -> User),
                        // pas teachers.id — voir même correctif dans AttendanceController::store().
                        'recorded_by' => $teacher->user_id,
                    ]
                );
            }
        });

        return redirect()->route('professeur.teaching-sessions.index')->with('success', "Séance pointée pour {$assignment->classroom->name}.");
    }
}
