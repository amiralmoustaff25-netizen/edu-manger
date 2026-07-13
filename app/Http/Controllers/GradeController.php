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

        $matieres = Matiere::all();

        return view('teachers.grades.index', compact('classrooms', 'matieres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.valeur' => 'nullable|numeric|min:0|max:20',
            'grades.*.appreciation' => 'nullable|string',
        ]);

        $teacher = auth()->user();
        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $matiere = Matiere::findOrFail($validated['matiere_id']);

        // Vérifier que le professeur est assigné à cette classe
        if (!$teacher->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            abort(403, 'Vous n\'êtes pas autorisé à saisir des notes pour cette classe.');
        }

        $savedCount = 0;

        foreach ($validated['grades'] as $gradeData) {
            // Ignorer les notes vides
            if (!isset($gradeData['valeur']) || $gradeData['valeur'] === '') {
                continue;
            }

            Note::updateOrCreate(
                [
                    'user_id' => $gradeData['user_id'],
                    'classroom_id' => $classroom->id,
                    'matiere_id' => $matiere->id,
                    'type_evaluation' => $validated['type_evaluation'],
                    'periode' => $validated['periode'],
                ],
                [
                    'valeur' => $gradeData['valeur'],
                    'appreciation' => $gradeData['appreciation'] ?? null,
                ]
            );

            $savedCount++;
        }

        return redirect()
            ->route('professeur.grades.index', ['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id])
            ->with('success', "{$savedCount} note(s) enregistrée(s) avec succès.");
    }
}
