<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherClassController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        // Les classes du professeur proviennent de ses affectations pédagogiques
        // actives (PedagogicalAssignment), pas de l'ancienne table pivot
        // teacher_classroom qui n'est plus alimentée — voir TeacherDashboardController.
        $classroomIds = $teacher->pedagogicalAssignments()
            ->where('is_active', true)
            ->pluck('classroom_id')
            ->unique();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->with(['schoolYear', 'teacher'])
            ->withCount(['registrations' => fn ($query) => $query->where('status', 'active')])
            ->get();

        return view('teachers.classes.index', compact('classrooms'));
    }

    public function show(Classroom $classroom)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        // Vérifier que le professeur est bien assigné à cette classe
        $isAssigned = $teacher->pedagogicalAssignments()
            ->where('is_active', true)
            ->where('classroom_id', $classroom->id)
            ->exists();

        if (! $isAssigned) {
            abort(403, 'Vous n\'êtes pas autorisé à accéder à cette classe.');
        }

        $classroom->load(['schoolYear', 'teacher', 'registrations.user']);

        $students = $classroom->registrations()
            ->with('user')
            ->where('status', 'active')
            ->get()
            ->map(function ($registration) {
                return $registration->user;
            });

        return view('teachers.classes.show', compact('classroom', 'students'));
    }
}
