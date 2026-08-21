<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherClassController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        // Les classes du professeur proviennent soit de ses affectations pédagogiques
        // actives (PedagogicalAssignment) quand il y enseigne une matière, soit du fait
        // qu'il en est le titulaire (Classroom.teacher_id) même sans matière assignée —
        // voir TeacherDashboardController pour la même règle et son historique.
        $classroomIds = $teacher->pedagogicalAssignments()
            ->where('is_active', true)
            ->pluck('classroom_id')
            ->merge(Classroom::where('teacher_id', $user->id)->pluck('id'))
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

        if (! $teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        // Vérifier que le professeur est bien assigné à cette classe (matière) ou qu'il
        // en est le titulaire (voir index() ci-dessus pour la même règle).
        $isAssigned = $teacher->pedagogicalAssignments()
            ->where('is_active', true)
            ->where('classroom_id', $classroom->id)
            ->exists() || $classroom->teacher_id === $user->id;

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
