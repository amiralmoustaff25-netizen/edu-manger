<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Sanction;
use App\Models\User;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;

class ParentPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $parent = $user->parentProfile()
            ->with(['students.latestRegistration.classroom.schoolYear'])
            ->firstOrFail();

        $parent->setRelation('students', $parent->students->loadCount('notes', 'attendances', 'sanctions'));

        $studentIds = $parent->students->pluck('id');

        $recentAttendances = Attendance::whereIn('user_id', $studentIds)
            ->with('student')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $sanctions = Sanction::whereIn('user_id', $studentIds)
            ->with('student')
            ->orderByDesc('date_incident')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('parents.dashboard', [
            'parent' => $parent,
            'recentAttendances' => $recentAttendances,
            'sanctions' => $sanctions,
        ]);
    }

    public function childrenIndex(Request $request)
    {
        $parent = $request->user()->parentProfile()->with(['students.latestRegistration.classroom.schoolYear'])->firstOrFail();

        return view('parents.children.index', compact('parent'));
    }

    /**
     * Résout l'élève ciblé par la requête en le limitant strictement aux enfants
     * du parent connecté (protection IDOR : ne jamais faire confiance à l'ID brut).
     */
    protected function resolveStudentFromRequest(Request $request): ?User
    {
        $studentId = $request->input('student') ?? $request->query('student');
        if (! $studentId) {
            return null;
        }

        $parent = $request->user()->parentProfile;
        if (! $parent) {
            return null;
        }

        return $parent->students()->where('users.id', $studentId)->first();
    }

    public function childProfile(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return app(\App\Http\Controllers\StudentController::class)->show($student, app(\App\Services\FeeService::class));
        }

        return redirect()->route('parents.dashboard');
    }

    public function childNotes(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return app(\App\Http\Controllers\StudentNotesController::class)->show($request, $student);
        }

        return redirect()->route('parents.dashboard');
    }

    public function childBulletins(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('bulletins.show', [$student, 'trimestre_1']);
        }

        return redirect()->route('parents.dashboard');
    }

    public function childAttendances(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'attendances');
        }

        return redirect()->route('parents.dashboard');
    }

    public function childDiscipline(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'sanctions');
        }

        return redirect()->route('parents.dashboard');
    }

    public function childTimetable(Request $request, TimetableGridService $timetableGrid)
    {
        $student = $this->resolveStudentFromRequest($request);
        if (! $student) {
            return redirect()->route('parents.dashboard');
        }

        $registration = $student->latestRegistration;
        $registration?->load(['classroom.schoolYear', 'schoolYear']);

        $timetableEntries = $registration?->classroom
            ? $timetableGrid->grid($registration->classroom)
            : null;

        return view('students.timetable', ['user' => $student, 'registration' => $registration, 'timetableEntries' => $timetableEntries]);
    }

    public function childPayments(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'payments');
        }

        return redirect()->route('parents.dashboard');
    }

    public function calendar(Request $request)
    {
        return view('parents.calendar');
    }
}
