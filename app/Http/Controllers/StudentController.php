<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStudentRequest;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-eleves');

        $students = User::query()
            ->role('eleve')
            ->with(['latestRegistration.classroom', 'latestRegistration.schoolYear'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('registrations', fn ($query) => $query->where('matricule', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('classroom_id'), function ($query) use ($request) {
                $query->whereHas('registrations', fn ($query) => $query->where('classroom_id', $request->integer('classroom_id')));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->string('status')->toString();

                if (in_array($status, ['pending', 'active'], true)) {
                    $query->whereHas('registrations', fn ($query) => $query->where('status', $status));
                }

                if ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'classrooms' => Classroom::orderBy('name')->get(),
            'filters' => $request->only(['search', 'classroom_id', 'status']),
        ]);
    }

    public function show(User $student): View
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless($student->hasRole('eleve'), 404);

        $student->load([
            'registrations' => fn ($query) => $query->with(['classroom', 'schoolYear', 'payments'])->latest(),
        ]);

        $currentRegistration = $student->registrations->first();
        $totalPaid = $student->registrations->flatMap->payments->sum('amount');
        $remainingBalance = $student->registrations->flatMap->payments->sum('remaining_balance');

        return view('students.show', [
            'student' => $student,
            'currentRegistration' => $currentRegistration,
            'classrooms' => Classroom::orderBy('name')->get(),
            'totalPaid' => $totalPaid,
            'remainingBalance' => $remainingBalance,
        ]);
    }

    public function transfer(TransferStudentRequest $request, User $student)
    {
        $this->authorize('transferer-eleve', $student);

        abort_unless($student->hasRole('eleve'), 404);

        $validated = $request->validated();

        $registration = Registration::where('user_id', $student->id)->findOrFail($validated['registration_id']);
        $registration->update(['classroom_id' => $validated['classroom_id']]);

        return back()->with('success', 'Classe de l\'élève mise à jour.');
    }

    public function updateStatus(UpdateStudentStatusRequest $request, User $student)
    {
        $this->authorize('modifier-statut-eleve', $student);

        abort_unless($student->hasRole('eleve'), 404);

        $validated = $request->validated();

        $registration = Registration::where('user_id', $student->id)->findOrFail($validated['registration_id']);
        $registration->update(['status' => $validated['status']]);

        $student->update(['is_active' => $validated['status'] === 'active']);

        return back()->with('success', 'Statut de l\'élève mis à jour.');
    }
}