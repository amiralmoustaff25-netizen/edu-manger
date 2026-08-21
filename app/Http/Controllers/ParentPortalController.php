<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\Reminder;
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

        $parent->setRelation('students', $parent->students->loadCount('notes', 'attendances'));

        // Rappels de paiement des enfants de ce parent : les rappels générés
        // (ReminderService) n'étaient jusqu'ici visibles que via la page "Rappels"
        // interne (super-admin/manager-comptable), jamais transmis au parent
        // concerné — aucun canal d'envoi réel (email/SMS) n'est en place, seul
        // ce tableau de bord les rend effectivement visibles au bon destinataire.
        $registrationIds = $parent->students->pluck('latestRegistration.id')->filter();
        $reminders = Reminder::pending()
            ->whereIn('registration_id', $registrationIds)
            ->with('registration.user')
            ->orderBy('scheduled_at')
            ->get();

        return view('parents.dashboard', compact('parent', 'reminders'));
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

    public function childTimetable(Request $request, TimetableGridService $timetableGrid)
    {
        $student = $this->resolveStudentFromRequest($request);
        if (! $student) {
            return redirect()->route('parents.dashboard');
        }

        $registration = $student->latestRegistration;
        $registration?->load(['classroom.teachers.user', 'classroom.schoolYear', 'schoolYear']);
        $matieres = Matiere::all()->keyBy('id');

        $timetableEntries = null;
        if ($registration?->classroom?->cycle === 'primaire') {
            $timetableEntries = $timetableGrid->grid($registration->classroom);
        }

        return view('students.timetable', ['user' => $student, 'registration' => $registration, 'matieres' => $matieres, 'timetableEntries' => $timetableEntries]);
    }

    public function childPayments(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        if ($student) {
            return redirect()->route('parents.children.profile', ['student' => $student->id])->with('focus', 'payments');
        }

        return redirect()->route('parents.dashboard');
    }

    public function messaging(Request $request)
    {
        return view('parents.messaging');
    }

    public function calendar(Request $request)
    {
        return view('parents.calendar');
    }
}
