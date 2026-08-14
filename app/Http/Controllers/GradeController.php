<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\Teacher;
use App\Models\User;
use App\Http\Requests\StoreGradeRequest;
use App\Services\AuditLogService;
use App\Services\SchoolYearGuardService;
use App\Support\EvaluationTypeScope;
use Illuminate\Http\Request;

class GradeController extends Controller
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

        $matieres = Matiere::all();

        return view('teachers.grades.index', compact('classrooms', 'matieres'));
    }

    public function store(StoreGradeRequest $request, SchoolYearGuardService $schoolYearGuard)
    {
        $validated = $request->validated();

        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $matiere = Matiere::findOrFail($validated['matiere_id']);

        // MET-03 : aucun verrou n'empêchait la saisie/modification de notes pour une
        // classe d'une année scolaire déjà clôturée (seuls les modules financiers
        // étaient protégés par ce garde-fou).
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);

        // Le type d'évaluation autorisé dépend du cycle (le primaire n'a que la
        // composition) : le formulaire ne propose déjà que les bonnes options, mais
        // cette règle métier doit aussi être imposée côté serveur, pas seulement dans
        // le menu déroulant, sans quoi un envoi direct du formulaire la contournait.
        if (!in_array($validated['type_evaluation'], EvaluationTypeScope::allowedFor($classroom->cycle), true)) {
            abort(422, "Ce type d'évaluation n'est pas autorisé pour ce cycle.");
        }

        // Vérifier que le professeur est assigné à cette classe ET cette matière
        $isAssigned = $teacher->classrooms()
            ->where('classrooms.id', $classroom->id)
            ->wherePivot('matiere_id', $matiere->id)
            ->exists();

        if (!$isAssigned) {
            abort(403, 'Vous n\'êtes pas autorisé à saisir des notes pour cette matière dans cette classe.');
        }

        // Empêche un professeur d'enregistrer une note pour un élève qui n'est pas
        // réellement inscrit dans cette classe (le user_id vient du formulaire).
        $enrolledStudentIds = Registration::where('classroom_id', $classroom->id)
            ->where('status', 'active')
            ->pluck('user_id');

        foreach ($validated['grades'] as $gradeData) {
            if (!$enrolledStudentIds->contains((int) $gradeData['user_id'])) {
                abort(403, "Un ou plusieurs élèves ne sont pas inscrits dans cette classe.");
            }
        }

        // Les notes déjà validées sont verrouillées : toute la saisie est rejetée pour éviter
        // une modification partielle silencieuse. Un privilégié doit d'abord les rouvrir (reopen()).
        $hasValidatedNotes = Note::where('classroom_id', $classroom->id)
            ->where('matiere_id', $matiere->id)
            ->where('type_evaluation', $validated['type_evaluation'])
            ->where('periode', $validated['periode'])
            ->whereIn('user_id', collect($validated['grades'])->pluck('user_id'))
            ->validated()
            ->exists();

        if ($hasValidatedNotes) {
            return back()->withInput()->with('error', 'Ces notes ont déjà été validées par l\'administration et sont verrouillées. Une réouverture privilégiée est nécessaire avant toute modification.');
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
            ->route('professeur.notes.index', ['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id])
            ->with('success', "{$savedCount} note(s) enregistrée(s) avec succès.");
    }

    /**
     * Rechercher un élève par matricule et afficher directement toutes les matières
     * (avec coefficients) qu'il doit à ce professeur pour la période choisie.
     */
    public function searchStudent(Request $request)
    {
        $teacher = Teacher::where('user_id', auth()->id())->first();

        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        $periode = $request->input('periode', 'trimestre_1');
        $student = null;
        $classroom = null;
        $assignments = collect();
        $existingNotes = collect();

        if ($request->filled('matricule')) {
            // Allow searching by matricule regardless of role column, some fixtures set role via 'role' attribute
            $matricule = $request->input('matricule');
            $student = User::where(function ($query) use ($matricule) {
                $query->where('matricule', $matricule)
                    ->where('role', 'eleve');
            })
                ->orWhere(function ($q) use ($matricule) {
                    $q->where('matricule', $matricule)
                        ->whereHas('roles', function ($r) {
                            $r->where('name', 'eleve');
                        });
                })
                ->first();

            if (!$student) {
                return back()->withInput()->with('error', 'Aucun élève ne correspond à ce matricule.');
            }

            $registration = $student->latestRegistration;
            $classroom = $registration?->classroom;

            if (!$classroom) {
                return back()->withInput()->with('error', "Cet élève n'a pas de classe active.");
            }

            $assignments = PedagogicalAssignment::with('matiere')
                ->where('teacher_id', $teacher->id)
                ->where('classroom_id', $classroom->id)
                ->where('is_active', true)
                ->get();

            if ($assignments->isEmpty()) {
                return back()->withInput()->with('error', "Cet élève n'est pas dans une de vos classes affectées.");
            }

            $existingNotes = Note::where('user_id', $student->id)
                ->where('periode', $periode)
                ->whereIn('matiere_id', $assignments->pluck('matiere_id'))
                ->get()
                ->keyBy(fn ($note) => $note->matiere_id.'_'.$note->type_evaluation);
        }

        return view('teachers.grades.student', compact('student', 'classroom', 'assignments', 'periode', 'existingNotes'));
    }

    /**
     * Enregistrer les notes d'un seul élève, toutes matières confondues, pour une période donnée.
     */
    public function storeForStudent(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'periode' => ['required', 'string'],
            'grades' => ['required', 'array'],
            'grades.*.matiere_id' => ['required', 'exists:matieres,id'],
            'grades.*.type_evaluation' => ['required', 'string'],
            'grades.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'grades.*.appreciation' => ['nullable', 'string'],
        ]);

        $teacher = Teacher::where('user_id', auth()->id())->first();

        if (!$teacher) {
            abort(403, 'Profil enseignant non trouvé.');
        }

        $classroom = Classroom::findOrFail($validated['classroom_id']);

        // MET-03 : voir store() ci-dessus — même verrou requis sur ce second point
        // d'entrée de saisie (une matière à la fois, par élève, sur toutes matières).
        $schoolYearGuard->assertNotLocked($classroom->schoolYear);
        $assignedMatiereIds = PedagogicalAssignment::where('teacher_id', $teacher->id)
            ->where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->pluck('matiere_id');

        // Empêche un professeur d'enregistrer une note pour un élève qui n'est pas
        // réellement inscrit dans cette classe (classroom_id/user_id viennent de
        // champs cachés du formulaire, donc falsifiables côté client).
        $isEnrolled = Registration::where('user_id', $validated['user_id'])
            ->where('classroom_id', $classroom->id)
            ->where('status', 'active')
            ->exists();

        if (!$isEnrolled) {
            abort(403, "Cet élève n'est pas inscrit dans cette classe.");
        }

        $savedCount = 0;

        foreach ($validated['grades'] as $gradeData) {
            if (!isset($gradeData['valeur']) || $gradeData['valeur'] === '') {
                continue;
            }

            if (!$assignedMatiereIds->contains((int) $gradeData['matiere_id'])) {
                abort(403, "Vous n'êtes pas autorisé à saisir des notes pour une de ces matières dans cette classe.");
            }

            if (!in_array($gradeData['type_evaluation'], EvaluationTypeScope::allowedFor($classroom->cycle), true)) {
                abort(422, "Ce type d'évaluation n'est pas autorisé pour ce cycle.");
            }

            $hasValidatedNote = Note::where('user_id', $validated['user_id'])
                ->where('classroom_id', $classroom->id)
                ->where('matiere_id', $gradeData['matiere_id'])
                ->where('type_evaluation', $gradeData['type_evaluation'])
                ->where('periode', $validated['periode'])
                ->validated()
                ->exists();

            if ($hasValidatedNote) {
                continue;
            }

            Note::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'classroom_id' => $classroom->id,
                    'matiere_id' => $gradeData['matiere_id'],
                    'type_evaluation' => $gradeData['type_evaluation'],
                    'periode' => $validated['periode'],
                ],
                [
                    'valeur' => $gradeData['valeur'],
                    'appreciation' => $gradeData['appreciation'] ?? null,
                ]
            );

            $savedCount++;
        }

        $student = User::find($validated['user_id']);

        return redirect()
            ->route('professeur.notes.eleve', ['matricule' => $student?->matricule, 'periode' => $validated['periode']])
            ->with('success', "{$savedCount} note(s) enregistrée(s) avec succès.");
    }

    /**
     * Valider (verrouiller) un lot de notes pour une classe / matière / évaluation / période.
     * Une fois validées, ces notes ne peuvent plus être modifiées par le professeur.
     */
    public function validateNotes(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('validateNotes', Note::class);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
        ]);

        $schoolYearGuard->assertNotLocked(Classroom::findOrFail($validated['classroom_id'])->schoolYear);

        $notes = Note::where($validated)->notValidated()->get();

        foreach ($notes as $note) {
            $note->validate(auth()->id());
        }

        app(AuditLogService::class)->log('validated', Note::class, null, null, $validated, $notes->count().' note(s) validée(s) et verrouillée(s)');

        return back()->with('success', $notes->count().' note(s) validée(s) et verrouillée(s) avec succès.');
    }

    /**
     * Rouvrir un lot de notes validées : action privilégiée réservée au super-admin.
     */
    public function reopenNotes(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $this->authorize('reopen', Note::class);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'periode' => 'required|string',
        ]);

        $schoolYearGuard->assertNotLocked(Classroom::findOrFail($validated['classroom_id'])->schoolYear);

        $notes = Note::where($validated)->validated()->get();

        foreach ($notes as $note) {
            $note->reopen();
        }

        app(AuditLogService::class)->log('reopened', Note::class, null, null, $validated, $notes->count().' note(s) rouverte(s) pour modification');

        return back()->with('success', $notes->count().' note(s) rouverte(s) avec succès.');
    }
}
