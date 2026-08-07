<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use Illuminate\Http\Request;

class ParentPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $parent = $user->parentProfile()->with(['students.latestRegistration.classroom.schoolYear'])->firstOrFail();

        return view('parents.dashboard', compact('parent'));
    }

    public function childrenIndex(Request $request)
    {
        $parent = $request->user()->parentProfile()->with(['students.latestRegistration.classroom.schoolYear'])->firstOrFail();

        return view('parents.children.index', compact('parent'));
    }

    protected function resolveStudentFromRequest(Request $request)
    {
        $studentId = $request->input('student') ?? $request->query('student');
        if (! $studentId) {
            return null;
        }

        return \App\Models\User::find($studentId);
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
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'notes');
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

    public function childTimetable(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('student.timetable');
        }

        return redirect()->route('parents.dashboard');
    }

    public function childPayments(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'payments');
        }

        return redirect()->route('parents.dashboard');
    }

    // Messaging and calendar stubs (could point to existing controllers)
    public function messaging(Request $request)
    {
        return redirect()->route('notifications.index');
    }

    public function calendar(Request $request)
    {
        return view('parents.calendar');
    }
}
