<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SchoolYearContext;
use App\Services\SchoolYearGuardService;
use App\Support\ClassroomLevel;
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

    public function index(SchoolYearContext $context)
    {
        Gate::authorize('viewAny', Classroom::class);

        $viewingYear = $context->current();

        $classrooms = Classroom::with('teacher')
            ->when($viewingYear, fn ($query) => $query->where('school_year_id', $viewingYear->id))
            ->withCount([
                'registrations as students_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('ordre')
            ->orderBy('name')
            ->get();

        return view('classrooms.index', compact('classrooms', 'viewingYear'));
    }

    public function create()
    {
        Gate::authorize('create', Classroom::class);

        return view('classrooms.create');
    }

    public function store(StoreClassroomRequest $request)
    {
        Gate::authorize('create', Classroom::class);
        $validated = $request->validated();

        $teacherId = $validated['teacher_id'] ?? null;

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'].($validated['section'] ? ' '.$validated['section'] : '');
        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        $classroom = Classroom::create([
            'name' => $fullName,
            'cycle' => $cycle,
            'serie' => $cycle === 'lycee' ? ($validated['serie'] ?? null) : null,
            'ordre' => ClassroomLevel::ordre($validated['level']),
            'school_year_id' => $activeYear->id,
            'teacher_id' => $teacherId,
            'max_students' => $validated['max_students'],
        ]);

        $this->syncPrimaryTeacherAssignments($classroom, $teacherId);

        return redirect()->route('classrooms.index')->with('success', 'Classe créée avec succès.');
    }

    public function edit(Classroom $classroom)
    {
        Gate::authorize('update', $classroom);

        // La gestion des enseignants affectés (ex. classrooms.teachers, page séparée) est
        // désormais intégrée directement à cette page de modification, plutôt qu'un lien
        // "Enseignants" distinct dans la liste des classes.
        $classroom->load(['teachers' => function ($query) {
            $query->with('user')->withPivot('matiere_id', 'volume_horaire_hebdo');
        }]);

        $teachers = Teacher::with('user')->get();
        $matieres = Matiere::all();
        $activeYear = SchoolYear::where('is_active', true)->first();
        $canManageTeachers = Gate::allows('gerer-enseignants-classe');

        return view('classrooms.edit', compact('classroom', 'teachers', 'matieres', 'activeYear', 'canManageTeachers'));
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom, SchoolYearGuardService $schoolYearGuard)
    {
        Gate::authorize('update', $classroom);
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);

        $validated = $request->validated();

        $teacherId = $validated['teacher_id'] ?? null;

        $cycle = $this->determineCycle($validated['level']);
        $fullName = $validated['level'].($validated['section'] ? ' '.$validated['section'] : '');

        // Mise à jour avec le professeur et le nombre max d'élèves
        $classroom->update([
            'name' => $fullName,
            'cycle' => $cycle,
            'serie' => $cycle === 'lycee' ? ($validated['serie'] ?? null) : null,
            'ordre' => ClassroomLevel::ordre($validated['level']),
            'teacher_id' => $teacherId,
            'max_students' => $validated['max_students'],
        ]);

        $this->syncPrimaryTeacherAssignments($classroom, $teacherId);

        return redirect()->route('classrooms.index')->with('success', 'Classe modifiée avec succès.');
    }

    /**
     * Pour une classe de primaire, désigner l'enseignant titulaire (Classroom::teacher_id,
     * qui référence users.id — cf. BaseClassroomRequest) suffit désormais : plus besoin de
     * dupliquer la même information dans "Affectations pédagogiques" pour que le professeur
     * principal couvre les matières générales de la classe (nécessaire aux bulletins/notes,
     * voir PedagogicalAssignment, qui référence teachers.id). On resynchronise ici
     * automatiquement les PedagogicalAssignment "matières générales" de la classe :
     * désactivées pour tout autre professeur, créées/réactivées pour le nouveau titulaire.
     */
    private function syncPrimaryTeacherAssignments(Classroom $classroom, ?int $titulaireUserId): void
    {
        if ($classroom->cycle !== 'primaire' || ! $classroom->school_year_id) {
            return;
        }

        $generalMatiereIds = Matiere::generalSubjectIds();
        if ($generalMatiereIds->isEmpty()) {
            return;
        }

        $teacher = $titulaireUserId ? Teacher::where('user_id', $titulaireUserId)->first() : null;

        PedagogicalAssignment::where('classroom_id', $classroom->id)
            ->where('school_year_id', $classroom->school_year_id)
            ->whereIn('matiere_id', $generalMatiereIds)
            ->when($teacher, fn ($query) => $query->where('teacher_id', '!=', $teacher->id))
            ->update(['is_active' => false, 'deactivated_at' => now()]);

        if (! $teacher) {
            return;
        }

        foreach ($generalMatiereIds as $matiereId) {
            PedagogicalAssignment::updateOrCreate(
                ['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiereId, 'school_year_id' => $classroom->school_year_id],
                ['volume_horaire_hebdo' => 0, 'is_active' => true, 'deactivated_at' => null]
            );
        }
    }

    public function destroy(Classroom $classroom, SchoolYearGuardService $schoolYearGuard)
    {
        Gate::authorize('delete', $classroom);
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);

        if ($classroom->registrations()->exists()) {
            return back()->withErrors(['classroom' => 'Impossible de supprimer cette classe : des inscriptions y sont rattachées.']);
        }

        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'La classe a été supprimée.');
    }

    public function attachTeacher(Request $request, Classroom $classroom, SchoolYearGuardService $schoolYearGuard)
    {
        Gate::authorize('gerer-enseignants-classe');
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);

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

    public function detachTeacher(Classroom $classroom, Teacher $teacher, SchoolYearGuardService $schoolYearGuard)
    {
        Gate::authorize('gerer-enseignants-classe');
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);

        $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

        $classroom->teachers()
            ->where('teacher_id', $teacher->id)
            ->wherePivot('annee_scolaire', $activeYear->year_string)
            ->detach();

        return back()->with('success', 'Professeur retiré de la classe avec succès.');
    }
}
