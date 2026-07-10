<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Registration;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
        $classrooms = $teacher->classrooms()
            ->with(['schoolYear'])
            ->get();

        return view('teachers.grades.index', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.matiere_id' => 'required|exists:matieres,id',
            'grades.*.valeur' => 'required|numeric|min:0|max:20',
            'grades.*.type_evaluation' => 'required|string',
            'grades.*.periode' => 'required|string',
            'grades.*.appreciation' => 'nullable|string',
        ]);

        $teacher = auth()->user();
        $classroom = Classroom::findOrFail($validated['classroom_id']);

        // Vérifier que le professeur est assigné à cette classe
        if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            abort(403, 'Vous n\'êtes pas autorisé à saisir des notes pour cette classe.');
        }

        foreach ($validated['grades'] as $gradeData) {
            Note::updateOrCreate(
                [
                    'user_id' => $gradeData['user_id'],
                    'classroom_id' => $classroom->id,
                    'matiere_id' => $gradeData['matiere_id'],
                    'type_evaluation' => $gradeData['type_evaluation'],
                    'periode' => $gradeData['periode'],
                ],
                [
                    'valeur' => $gradeData['valeur'],
                    'appreciation' => $gradeData['appreciation'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('professeur.grades.index')
            ->with('success', 'Les notes ont été enregistrées avec succès.');
    }
}
