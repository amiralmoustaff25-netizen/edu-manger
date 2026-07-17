<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassroomController extends Controller
{
    private function determineCycle(string $level): string
    {
        return match ($level) {
            'CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2' => 'primaire',
            '6ème', '5ème', '4ème', '3ème' => 'college',
            'Seconde', 'Première', 'Terminale' => 'lycee',
            default => 'primaire',
        };
    }

    public function index()
    {
        Gate::authorize('viewAny', Classroom::class);
        $classrooms = Classroom::all();

        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        Gate::authorize('create', Classroom::class);

        return view('classrooms.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Classroom::class);
        $validated = $request->validate([
            'level' => 'required|string',
            'section' => 'nullable|string',
            'teacher_id' => 'nullable|exists:users,id',
            'max_students' => 'required|integer|min:1|max:60',
        ]);

        // Vérifier que le teacher_id est bien un professeur
        if ($validated['teacher_id']) {
            $teacher = User::findOrFail($validated['teacher_id']);
            if (! $teacher->hasRole('professeur')) {
                return back()->withErrors(['teacher_id' => 'L\'utilisateur sélectionné n\'est pas un professeur.']);
            }
        }

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'].($validated['section'] ? ' '.$validated['section'] : '');
        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        Classroom::create([
            'name' => $fullName,
            'cycle' => $cycle,
            'school_year_id' => $activeYear->id,
            'teacher_id' => $validated['teacher_id'],
            'max_students' => $validated['max_students'],
        ]);

        return redirect()->route('classrooms.index')->with('success', 'Classe créée avec succès.');
    }

    public function edit(Classroom $classroom)
    {
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
            'max_students' => 'required|integer|min:1|max:60',
        ]);

        // Vérifier que le teacher_id est bien un professeur
        if ($validated['teacher_id']) {
            $teacher = User::findOrFail($validated['teacher_id']);
            if (! $teacher->hasRole('professeur')) {
                return back()->withErrors(['teacher_id' => 'L\'utilisateur sélectionné n\'est pas un professeur.']);
            }
        }

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'].($validated['section'] ? ' '.$validated['section'] : '');

        // Mise à jour avec le professeur et le nombre max d'élèves
        $classroom->update([
            'name' => $fullName,
            'cycle' => $cycle,
            'teacher_id' => $validated['teacher_id'],
            'max_students' => $validated['max_students'],
        ]);

        return redirect()->route('classrooms.index')->with('success', 'Classe modifiée avec succès.');
    }

    public function destroy(Classroom $classroom)
    {
        Gate::authorize('delete', $classroom);
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'La classe a été supprimée.');
    }

    public function teachers(Classroom $classroom)
    {
        Gate::authorize('gerer-enseignants-classe');

        $classroom->load(['teachers' => function ($query) {
            $query->with('user')->withPivot('matiere_id', 'volume_horaire_hebdo');
        }]);

        $teachers = Teacher::with('user')->get();
        $matieres = Matiere::all();
        $activeYear = SchoolYear::where('is_active', true)->first();

        return view('classrooms.teachers', compact('classroom', 'teachers', 'matieres', 'activeYear'));
    }

    public function attachTeacher(Request $request, Classroom $classroom)
    {
        Gate::authorize('gerer-enseignants-classe');

        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'volume_horaire_hebdo' => 'required|integer|min:1|max:30',
        ]);

        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        // Vérifier si le professeur est déjà affecté à cette classe pour cette année
        $existing = $classroom->teachers()
            ->where('teacher_id', $validated['teacher_id'])
            ->wherePivot('annee_scolaire', $activeYear->year_string)
            ->first();

        if ($existing) {
            return back()->withErrors(['teacher_id' => 'Ce professeur est déjà affecté à cette classe pour cette année scolaire.']);
        }

        $classroom->teachers()->attach($validated['teacher_id'], [
            'annee_scolaire' => $activeYear->year_string,
            'matiere_id' => $validated['matiere_id'],
            'volume_horaire_hebdo' => $validated['volume_horaire_hebdo'],
        ]);

        return back()->with('success', 'Professeur affecté à la classe avec succès.');
    }

    public function detachTeacher(Classroom $classroom, Teacher $teacher)
    {
        Gate::authorize('gerer-enseignants-classe');

        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        $classroom->teachers()
            ->where('teacher_id', $teacher->id)
            ->wherePivot('annee_scolaire', $activeYear->year_string)
            ->detach();

        return back()->with('success', 'Professeur retiré de la classe avec succès.');
    }
}
