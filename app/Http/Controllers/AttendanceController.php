<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Registration;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
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

        $teacher = auth()->user();
        $classroom = Classroom::findOrFail($validated['classroom_id']);

        // Vérifier que le professeur est assigné à cette classe
        if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            abort(403, 'Vous n\'êtes pas autorisé à enregistrer les absences pour cette classe.');
        }

        // Empêcher la modification des absences passées (règle métier)
        if (carbon($validated['date'])->lt(today()->subDays(7))) {
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
}
