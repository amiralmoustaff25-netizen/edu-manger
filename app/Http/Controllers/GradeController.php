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

        // PedagogicalAssignment (écran "Affectations pédagogiques") est la seule source
        // de vérité des affectations enseignant/classe/matière alimentée par
        // l'administration — l'ancien pivot teacher_classroom (Teacher::classrooms(),
        // écran "Gestion des enseignants" retiré de la navigation) n'est plus jamais
        // renseigné : une classe/matière affectée uniquement via l'écran actuel
        // n'apparaissait donc jamais ici ni dans le menu déroulant matière, et la
        // saisie était de toute façon rejetée par store() (même bug, corrigé ci-dessous).
        $assignments = PedagogicalAssignment::with(['classroom.schoolYear', 'matiere'])
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->get();

        $classrooms = $assignments->pluck('classroom')->unique('id')->values();

        $matieres = $request->filled('classroom_id')
            ? $assignments->where('classroom_id', $request->integer('classroom_id'))->pluck('matiere')->unique('id')->values()
            : collect();

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

        $evaluationNumber = $this->resolveEvaluationNumber($classroom->cycle, $validated['type_evaluation'], $validated['evaluation_number'] ?? 1);

        // Vérifier que le professeur est assigné à cette classe ET cette matière — via
        // PedagogicalAssignment, seule source de vérité alimentée par l'administration
        // (voir index() ci-dessus, même correctif).
        $isAssigned = PedagogicalAssignment::where('teacher_id', $teacher->id)
            ->where('classroom_id', $classroom->id)
            ->where('matiere_id', $matiere->id)
            ->where('is_active', true)
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
            ->where('evaluation_number', $evaluationNumber)
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
                    'evaluation_number' => $evaluationNumber,
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
     * Numéro d'évaluation (1er devoir, 2e devoir, ...) réellement applicable : le
     * primaire n'a qu'une seule évaluation par matière/période (composition, voir
     * EvaluationTypeScope), donc toujours 1 quel que soit ce qui a été soumis. Pour le
     * collège/lycée, borné à config('edu.max_evaluations_per_period') — "2 devoirs
     * maximum par matière et par semestre" (cahier des charges), configurable pour
     * pouvoir évoluer sans toucher au code.
     */
    private function resolveEvaluationNumber(?string $cycle, string $typeEvaluation, int $requested): int
    {
        if (! in_array($cycle, ['college', 'lycee'], true)) {
            return 1;
        }

        $max = (int) (config("edu.max_evaluations_per_period.{$typeEvaluation}") ?? 1);

        if ($requested < 1 || $requested > $max) {
            abort(422, "Le numéro d'évaluation doit être compris entre 1 et {$max} pour ce type d'évaluation.");
        }

        return $requested;
    }

    /**
     * Rechercher un élève par matricule et afficher directement toutes les matières
     * (avec coefficients) qu'il doit à ce professeur pour la période choisie.
     */
    public function searchStudent(Request $request, \App\Services\GradeCalculationService $gradeCalculationService)
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
        $baremes = collect();
        $usesBaremeSystem = false;

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
                ->keyBy(fn ($note) => $note->matiere_id.'_'.$note->type_evaluation.'_'.$note->evaluation_number);

            // Barème par matière (système "sunuBulletin" du primaire) : la note max saisie
            // et affichée n'est pas toujours /20, voir GradeCalculationService::resolveBareme().
            $usesBaremeSystem = $gradeCalculationService->usesBaremeSystem($classroom, $classroom->school_year_id);
            if ($usesBaremeSystem) {
                $baremes = $assignments->mapWithKeys(
                    fn (PedagogicalAssignment $assignment) => [
                        $assignment->matiere_id => $gradeCalculationService->resolveBareme($assignment->matiere, $classroom, $classroom->school_year_id),
                    ]
                );
            }
        }

        return view('teachers.grades.student', compact('student', 'classroom', 'assignments', 'periode', 'existingNotes', 'baremes', 'usesBaremeSystem'));
    }

    /**
     * Enregistrer les notes d'un seul élève, toutes matières confondues, pour une période donnée.
     */
    public function storeForStudent(Request $request, SchoolYearGuardService $schoolYearGuard, \App\Services\GradeCalculationService $gradeCalculationService)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'periode' => ['required', 'string'],
            'grades' => ['required', 'array'],
            'grades.*.matiere_id' => ['required', 'exists:matieres,id'],
            'grades.*.type_evaluation' => ['required', 'string'],
            'grades.*.evaluation_number' => ['nullable', 'integer', 'min:1'],
            // Pas de max fixe ici : chaque matière peut avoir son propre barème en
            // primaire (ex. Mathématiques /80) — vérifié plus bas une fois la classe
            // résolue, une seule règle statique ne peut pas varier par ligne du tableau.
            'grades.*.valeur' => ['nullable', 'numeric', 'min:0'],
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

            // Barème dynamique par matière (système "sunuBulletin" du primaire, ex.
            // Mathématiques /80) — une seule règle de validation statique ne peut pas
            // varier par ligne du tableau, vérifié ici une fois la matière connue.
            $matiereForGrade = Matiere::find($gradeData['matiere_id']);
            $maxValeur = $matiereForGrade
                ? $gradeCalculationService->resolveBareme($matiereForGrade, $classroom, $classroom->school_year_id)
                : 20;

            if ((float) $gradeData['valeur'] > $maxValeur) {
                return back()->withInput()->withErrors([
                    'grades' => "La note pour {$matiereForGrade?->nom} ne peut pas dépasser le barème de cette matière ({$maxValeur}).",
                ]);
            }

            if (!in_array($gradeData['type_evaluation'], EvaluationTypeScope::allowedFor($classroom->cycle), true)) {
                abort(422, "Ce type d'évaluation n'est pas autorisé pour ce cycle.");
            }

            $evaluationNumber = $this->resolveEvaluationNumber($classroom->cycle, $gradeData['type_evaluation'], $gradeData['evaluation_number'] ?? 1);

            $hasValidatedNote = Note::where('user_id', $validated['user_id'])
                ->where('classroom_id', $classroom->id)
                ->where('matiere_id', $gradeData['matiere_id'])
                ->where('type_evaluation', $gradeData['type_evaluation'])
                ->where('evaluation_number', $evaluationNumber)
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
                    'evaluation_number' => $evaluationNumber,
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
