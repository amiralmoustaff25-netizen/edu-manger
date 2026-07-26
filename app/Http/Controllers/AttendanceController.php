<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }
        
        $classrooms = $teacher->classrooms()
            ->with(['schoolYear'])
            ->get();

        $selectedDate = $request->date ?? today()->format('Y-m-d');
        $selectedClassroom = $request->classroom_id;

        $attendances = collect();
        $students = collect();

        if ($selectedClassroom) {
            $classroom = Classroom::findOrFail($selectedClassroom);
            
            // Vérifier que le professeur est assigné à cette classe
            if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
                abort(403, 'Vous n\'êtes pas autorisé à gérer les absences de cette classe.');
            }

            $students = $classroom->registrations()
                ->with('user')
                ->where('status', 'active')
                ->get()
                ->map(function ($registration) {
                    return $registration->user;
                });

            $attendances = Attendance::where('classroom_id', $selectedClassroom)
                ->where('date', $selectedDate)
                ->get()
                ->keyBy('user_id');
        }

        return view('teachers.attendances.index', compact(
            'classrooms',
            'students',
            'attendances',
            'selectedDate',
            'selectedClassroom'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }
        
        $classroom = Classroom::findOrFail($validated['classroom_id']);

        // Vérifier que le professeur est assigné à cette classe
        if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            abort(403, 'Vous n\'êtes pas autorisé à enregistrer les absences pour cette classe.');
        }

        // Empêcher la modification des absences passées (règle métier)
        if (Carbon::parse($validated['date'])->lt(today()->subDays(7))) {
            abort(403, 'Vous ne pouvez pas modifier les absences de plus de 7 jours.');
        }

        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'user_id' => $attendanceData['user_id'],
                    'classroom_id' => $classroom->id,
                    'date' => $validated['date'],
                ],
                [
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null,
                    'recorded_by' => $teacher->id,
                ]
            );
        }

        return redirect()
            ->route('professeur.attendances.index', [
                'classroom_id' => $classroom->id,
                'date' => $validated['date'],
            ])
            ->with('success', 'Les présences ont été enregistrées avec succès.');
    }

    public function history(Request $request)
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $classrooms = $teacher->classrooms()->with('schoolYear')->get();
        $selectedClassroom = $request->integer('classroom_id') ?: null;

        if ($selectedClassroom && ! $classrooms->contains('id', $selectedClassroom)) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter les présences de cette classe.');
        }

        $attendances = Attendance::query()
            ->with(['student', 'classroom', 'recordedBy'])
            ->when($selectedClassroom, fn ($query) => $query->where('classroom_id', $selectedClassroom), fn ($query) => $query->whereIn('classroom_id', $classrooms->pluck('id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', $request->date))
            ->latest('date')
            ->orderBy('classroom_id')
            ->orderBy('user_id')
            ->paginate(30)
            ->withQueryString();

        return view('teachers.attendances.history', compact('attendances', 'classrooms', 'selectedClassroom'));
    }

    public function overview(Request $request)
    {
        $classrooms = Classroom::with('schoolYear')->orderBy('name')->get();
        $selectedClassroom = $request->integer('classroom_id') ?: null;

        $attendances = Attendance::query()
            ->with(['student', 'classroom', 'recordedBy'])
            ->when($selectedClassroom, fn ($query) => $query->where('classroom_id', $selectedClassroom))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', $request->date))
            ->latest('date')
            ->orderBy('classroom_id')
            ->orderBy('user_id')
            ->paginate(50)
            ->withQueryString();

        return view('attendances.overview', compact('attendances', 'classrooms', 'selectedClassroom'));
    }
}
