<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassroomController extends Controller
{
    private function determineCycle(string $level): string 
    {
        return match($level) {
            'CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2' => 'primaire',
            '6ème', '5ème', '4ème', '3ème' => 'college',
            'Seconde', 'Première', 'Terminale' => 'lycee',
            default => 'primaire',
        };
    }

    public function index() {
        Gate::authorize('viewAny', Classroom::class);
        $classrooms = Classroom::all();
        return view('classrooms.index', compact('classrooms'));
    }

    public function create() {
        Gate::authorize('create', Classroom::class);
        return view('classrooms.create');
    }

    public function store(Request $request) {
        Gate::authorize('create', Classroom::class);
        $validated = $request->validate(['level' => 'required|string', 'section' => 'nullable|string']);

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'] . ($validated['section'] ? ' ' . $validated['section'] : '');
        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        Classroom::create([
            'name' => $fullName,
            'cycle' => $cycle,
            'school_year_id' => $activeYear->id,
        ]);

        return redirect()->route('classrooms.index')->with('success', 'Classe créée avec succès.');
    }

    public function edit(Classroom $classroom) {
        Gate::authorize('update', $classroom);
        return view('classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        Gate::authorize('update', $classroom);
        
        $validated = $request->validate([
            'level' => 'required|string',
            'section' => 'nullable|string',
            'teacher_id' => 'nullable|exists:users,id', 
        ]);

        // Vérifier que le teacher_id est bien un professeur
        if ($validated['teacher_id']) {
            $teacher = User::findOrFail($validated['teacher_id']);
            if (!$teacher->hasRole(['professeur', 'enseignant'])) {
                return back()->withErrors(['teacher_id' => 'L\'utilisateur sélectionné n\'est pas un professeur.']);
            }
        }

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'] . ($validated['section'] ? ' ' . $validated['section'] : '');

        // Mise à jour avec le professeur
        $classroom->update([
            'name' => $fullName,
            'cycle' => $cycle,
            'teacher_id' => $validated['teacher_id'],
        ]);

        return redirect()->route('classrooms.index')->with('success', 'Classe modifiée et enseignant mis à jour.');
    }

    public function destroy(Classroom $classroom) {
        Gate::authorize('delete', $classroom);
        $classroom->delete();
        return redirect()->route('classrooms.index')->with('success', 'La classe a été supprimée.');
    }
}