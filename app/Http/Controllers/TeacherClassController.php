<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Registration;
use Illuminate\Http\Request;

class TeacherClassController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
        $classrooms = $teacher->classrooms()
            ->with(['schoolYear', 'teacher'])
            ->withCount('registrations')
            ->get();

        return view('teachers.classes.index', compact('classrooms'));
    }

    public function show(Classroom $classroom)
    {
        $this->authorize('view', $classroom);

        $teacher = auth()->user();
        
        // Vérifier que le professeur est bien assigné à cette classe
        if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
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
