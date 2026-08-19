<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\EvaluationType;
use App\Models\GradeSetting;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
use App\Models\Teacher;
use App\Services\SchoolYearGuardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PedagogicalConfigurationController extends Controller
{
    public function __construct()
    {
        // SEC-02 : aucune de ces actions n'avait de contrôle d'autorisation
        // propre — seul le middleware de route (role:super-admin|admin,
        // retiré depuis) les protégeait, rendant impossible toute délégation
        // fine via Spatie. Un seul point de contrôle pour tout le contrôleur.
        $this->middleware(function ($request, $next) {
            $this->authorize('gerer-configuration-pedagogique');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $schoolYears = SchoolYear::orderByDesc('year_string')->get();
        $schoolYear = $request->filled('school_year_id') ? SchoolYear::findOrFail($request->integer('school_year_id')) : SchoolYear::getActive();

        if (! $schoolYear) {
            return view('pedagogical-configuration.index', compact('schoolYears', 'schoolYear'));
        }

        $assignments = PedagogicalAssignment::with(['teacher.user', 'classroom', 'matiere'])->where('school_year_id', $schoolYear->id)->get();
        $classrooms = Classroom::where('school_year_id', $schoolYear->id)->get();
        $configuredSubjects = SubjectConfiguration::where('school_year_id', $schoolYear->id)->get();
        $periods = $schoolYear->academicPeriods;
        $matieres = Matiere::orderBy('nom')->get();

        $issues = [
            ['label' => 'Classes sans affectation pédagogique', 'count' => $classrooms->filter(fn ($classroom) => ! $assignments->contains('classroom_id', $classroom->id))->count(), 'tab' => 'assignments'],
            ['label' => 'Professeurs sans affectation', 'count' => Teacher::whereDoesntHave('pedagogicalAssignments', fn ($query) => $query->where('school_year_id', $schoolYear->id)->where('is_active', true))->count(), 'tab' => 'assignments'],
            ['label' => 'Matières sans coefficient configuré', 'count' => Matiere::whereDoesntHave('configurations', fn ($query) => $query->where('school_year_id', $schoolYear->id)->where('is_active', true))->count(), 'tab' => 'subjects'],
            ['label' => 'Périodes non configurées', 'count' => $periods->isEmpty() ? 1 : 0, 'tab' => 'periods'],
        ];

        return view('pedagogical-configuration.index', compact('schoolYears', 'schoolYear', 'assignments', 'classrooms', 'configuredSubjects', 'periods', 'issues', 'matieres'));
    }

    public function assignments(Request $request)
    {
        $schoolYears = SchoolYear::orderByDesc('year_string')->get();
        $schoolYear = $request->filled('school_year_id') ? SchoolYear::findOrFail($request->integer('school_year_id')) : SchoolYear::getActive();
        // whereHas('classroom') exclut les affectations orphelines d'une classe supprimée
        // depuis (aucun garde-fou empêchant la suppression d'une classe qui a encore des
        // affectations actives) : sans ce filtre, la ligne correspondante fait planter le
        // rendu de la liste (relation classroom/teacher/matiere nulle) pour TOUS les admins.
        $query = PedagogicalAssignment::with(['teacher.user', 'classroom', 'matiere', 'schoolYear'])
            ->whereHas('classroom')->whereHas('teacher')->whereHas('matiere')
            ->latest('updated_at');
        if ($schoolYear) { $query->where('school_year_id', $schoolYear->id); }
        foreach (['teacher_id', 'classroom_id', 'matiere_id'] as $filter) { if ($request->filled($filter)) { $query->where($filter, $request->integer($filter)); } }

        // Pour griser côté formulaire les classes primaire qu'un professeur ne peut pas
        // se voir attribuer (règle "professeur principal" déjà appliquée côté serveur dans
        // storeAssignments(), voir plus bas) : qui est déjà principal de quelle classe, et
        // quelle classe a déjà son principal — indexé par matricule/id pour Alpine.js.
        $primaireClassroomPrincipals = [];
        $teacherPrimairePrincipalOf = [];

        if ($schoolYear) {
            PedagogicalAssignment::with(['teacher', 'classroom'])
                ->where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->whereHas('classroom', fn ($q) => $q->where('cycle', 'primaire'))
                ->whereHas('matiere', fn ($q) => $q->whereNotIn('nom', $this->specialistSubjectNames()))
                ->get()
                ->each(function (PedagogicalAssignment $assignment) use (&$primaireClassroomPrincipals, &$teacherPrimairePrincipalOf) {
                    $primaireClassroomPrincipals[$assignment->classroom_id] = [
                        'teacher_matricule' => $assignment->teacher->matricule,
                        'teacher_name' => $assignment->teacher->user?->name ?? '—',
                    ];
                    $teacherPrimairePrincipalOf[$assignment->teacher->matricule] = [
                        'classroom_id' => $assignment->classroom_id,
                        'classroom_name' => $assignment->classroom->name,
                    ];
                });
        }

        return view('pedagogical-configuration.assignments', [
            'assignments' => $query->paginate(20)->withQueryString(), 'schoolYear' => $schoolYear, 'schoolYears' => $schoolYears,
            'teachers' => Teacher::with('user')->orderBy('matricule')->get(), 'classrooms' => $schoolYear ? Classroom::where('school_year_id', $schoolYear->id)->orderBy('name')->get() : collect(), 'matieres' => Matiere::orderBy('nom')->get(),
            'primaireClassroomPrincipals' => $primaireClassroomPrincipals, 'teacherPrimairePrincipalOf' => $teacherPrimairePrincipalOf,
        ]);
    }

    public function storeAssignments(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $data = $request->validate([
            'teacher_matricule' => ['required', 'string', 'exists:teachers,matricule'],
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['exists:classrooms,id'],
            'classroom_volumes' => ['nullable', 'array'],
            'matiere_ids' => ['nullable', 'array'],
            'matiere_ids.*' => ['exists:matieres,id'],
            'new_subject_names' => ['nullable', 'string', 'max:1000'],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ], [
            'teacher_matricule.required' => 'Saisissez le matricule du professeur.',
            'teacher_matricule.exists' => 'Aucun professeur ne correspond à ce matricule.',
            'classroom_ids.required' => 'Sélectionnez au moins une classe.',
            'classroom_ids.min' => 'Sélectionnez au moins une classe.',
            'classroom_ids.*.exists' => 'Une classe sélectionnée est invalide.',
            'school_year_id.required' => 'Sélectionnez une année scolaire.',
        ]);

        $classrooms = Classroom::whereIn('id', $data['classroom_ids'])->get()->keyBy('id');

        // Le primaire n'a pas de volume horaire par matière à saisir (un seul professeur
        // principal couvre toutes les matières générales) — uniquement le secondaire
        // (collège/lycée) exige un volume entre 0 et 50h par classe sélectionnée.
        $volumeErrors = [];
        foreach ($data['classroom_ids'] as $classroomId) {
            if (($classrooms[$classroomId]->cycle ?? null) === 'primaire') {
                continue;
            }

            $volume = $request->input("classroom_volumes.$classroomId");
            if ($volume === null || $volume === '' || ! is_numeric($volume) || $volume < 0 || $volume > 50) {
                $volumeErrors["classroom_volumes.$classroomId"] = 'Indiquez un volume entre 0 et 50 heures pour chaque classe sélectionnée.';
            }
        }
        if ($volumeErrors !== []) {
            return back()->withInput()->withErrors($volumeErrors);
        }

        $schoolYearGuard->assertNotLocked(SchoolYear::findOrFail($data['school_year_id']));

        $teacher = Teacher::where('matricule', $data['teacher_matricule'])->firstOrFail();
        $subjectNames = collect(explode(',', $data['new_subject_names'] ?? ''))->map(fn ($name) => trim($name))->filter()->unique();
        $subjectIds = collect($data['matiere_ids'] ?? []);
        foreach ($subjectNames as $name) {
            $subjectIds->push(Matiere::firstOrCreate(['nom' => $name], ['coefficient' => 1])->id);
        }
        $subjectIds = $subjectIds->unique()->values();

        if ($subjectIds->isEmpty()) {
            return back()->withInput()->withErrors(['new_subject_names' => 'Sélectionnez ou saisissez au moins une matière.']);
        }

        // Règle "professeur principal" du primaire : une matière générale (tout sauf les
        // matières spécialisées comme anglais/musique) affectée dans une classe de
        // primaire désigne son professeur principal, exclusif dans les deux sens —
        // une classe n'en a qu'un, un professeur n'en est qu'un pour une seule classe.
        $subjects = Matiere::whereIn('id', $subjectIds)->get()->keyBy('id');
        $generalSubjectIds = $subjectIds->reject(fn ($id) => $this->isSpecialistSubject($subjects[$id]));

        if ($generalSubjectIds->isNotEmpty()) {
            foreach ($data['classroom_ids'] as $classroomId) {
                $classroom = $classrooms[$classroomId];
                if ($classroom->cycle !== 'primaire') {
                    continue;
                }

                $otherPrincipal = PedagogicalAssignment::with('teacher.user')
                    ->where('classroom_id', $classroomId)
                    ->where('school_year_id', $data['school_year_id'])
                    ->where('teacher_id', '!=', $teacher->id)
                    ->where('is_active', true)
                    ->whereHas('matiere', fn ($q) => $q->whereNotIn('nom', $this->specialistSubjectNames()))
                    ->first();

                if ($otherPrincipal) {
                    return back()->withInput()->withErrors([
                        'classroom_ids' => "La classe {$classroom->name} a déjà un professeur principal ({$otherPrincipal->teacher->user?->name}). Une classe de primaire ne peut avoir qu'un seul professeur principal (hors matières spécialisées comme l'anglais ou la musique).",
                    ]);
                }

                $principalElsewhere = PedagogicalAssignment::with('classroom')
                    ->where('teacher_id', $teacher->id)
                    ->where('school_year_id', $data['school_year_id'])
                    ->where('classroom_id', '!=', $classroomId)
                    ->where('is_active', true)
                    ->whereHas('classroom', fn ($q) => $q->where('cycle', 'primaire'))
                    ->whereHas('matiere', fn ($q) => $q->whereNotIn('nom', $this->specialistSubjectNames()))
                    ->first();

                if ($principalElsewhere) {
                    return back()->withInput()->withErrors([
                        'teacher_matricule' => "Ce professeur est déjà professeur principal de la classe {$principalElsewhere->classroom->name}. Un professeur principal de primaire ne peut être affecté qu'à une seule classe.",
                    ]);
                }
            }
        }

        $created = 0;
        DB::transaction(function () use ($data, $request, $teacher, $subjectIds, $classrooms, &$created) {
            foreach ($data['classroom_ids'] as $classroomId) {
                $isPrimaire = ($classrooms[$classroomId]->cycle ?? null) === 'primaire';
                $volume = $isPrimaire ? 0 : $request->input("classroom_volumes.$classroomId");

                foreach ($subjectIds as $matiereId) {
                    $assignment = PedagogicalAssignment::firstOrCreate(['teacher_id' => $teacher->id, 'classroom_id' => $classroomId, 'matiere_id' => $matiereId, 'school_year_id' => $data['school_year_id']], ['volume_horaire_hebdo' => $volume, 'is_active' => true]);
                    if (! $assignment->wasRecentlyCreated) {
                        $assignment->update(['volume_horaire_hebdo' => $volume, 'is_active' => true]);
                    }
                    if ($assignment->wasRecentlyCreated) { $created++; }
                }
            }
        });

        return redirect()->route('pedagogical-configuration.assignments', ['school_year_id' => $data['school_year_id']])->with('success', "$created affectation(s) pédagogique(s) créée(s).");
    }

    /**
     * Liste des noms de matières spécialisées du primaire (config, comparaison insensible
     * à la casse/accents) — voir isSpecialistSubject().
     */
    private function specialistSubjectNames(): array
    {
        return Matiere::query()
            ->get(['id', 'nom'])
            ->filter(fn (Matiere $matiere) => in_array(Str::of($matiere->nom)->lower()->ascii()->toString(), config('edu.primary_specialist_subjects'), true))
            ->pluck('nom')
            ->all();
    }

    /**
     * Une matière spécialisée du primaire (ex. anglais, musique) peut avoir son propre
     * professeur dédié en plus du professeur principal — voir config('edu.primary_specialist_subjects').
     */
    private function isSpecialistSubject(Matiere $matiere): bool
    {
        return in_array(Str::of($matiere->nom)->lower()->ascii()->toString(), config('edu.primary_specialist_subjects'), true);
    }

    public function toggleAssignment(PedagogicalAssignment $assignment, SchoolYearGuardService $schoolYearGuard)
    {
        $schoolYearGuard->assertNotLocked($assignment->schoolYear);

        $isActive = ! $assignment->is_active;
        $assignment->update(['is_active' => $isActive, 'deactivated_at' => $isActive ? null : now(), 'deactivated_by' => $isActive ? null : auth()->id()]);
        return back()->with('success', 'Statut de l’affectation mis à jour.');
    }

    public function updateAssignment(Request $request, PedagogicalAssignment $assignment, SchoolYearGuardService $schoolYearGuard)
    {
        $schoolYearGuard->assertNotLocked($assignment->schoolYear);

        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'volume_horaire_hebdo' => ['nullable', 'numeric', 'min:0', 'max:50'],
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        $matiere = Matiere::findOrFail($data['matiere_id']);

        // Mêmes règles d'exclusivité "professeur principal" du primaire que storeAssignments()
        // (voir plus haut) — une modification ne doit pas pouvoir les contourner.
        if ($classroom->cycle === 'primaire' && ! $this->isSpecialistSubject($matiere)) {
            $otherPrincipal = PedagogicalAssignment::with('teacher.user')
                ->where('classroom_id', $classroom->id)
                ->where('school_year_id', $assignment->school_year_id)
                ->where('teacher_id', '!=', $assignment->teacher_id)
                ->where('id', '!=', $assignment->id)
                ->where('is_active', true)
                ->whereHas('matiere', fn ($q) => $q->whereNotIn('nom', $this->specialistSubjectNames()))
                ->first();

            if ($otherPrincipal) {
                return back()->withInput()->withErrors([
                    'classroom_id' => "La classe {$classroom->name} a déjà un professeur principal ({$otherPrincipal->teacher->user?->name}).",
                ]);
            }

            $principalElsewhere = PedagogicalAssignment::with('classroom')
                ->where('teacher_id', $assignment->teacher_id)
                ->where('school_year_id', $assignment->school_year_id)
                ->where('classroom_id', '!=', $classroom->id)
                ->where('id', '!=', $assignment->id)
                ->where('is_active', true)
                ->whereHas('classroom', fn ($q) => $q->where('cycle', 'primaire'))
                ->whereHas('matiere', fn ($q) => $q->whereNotIn('nom', $this->specialistSubjectNames()))
                ->first();

            if ($principalElsewhere) {
                return back()->withInput()->withErrors([
                    'classroom_id' => "Ce professeur est déjà professeur principal de la classe {$principalElsewhere->classroom->name}.",
                ]);
            }
        }

        $assignment->update([
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'volume_horaire_hebdo' => $classroom->cycle === 'primaire' ? 0 : ($data['volume_horaire_hebdo'] ?? 0),
        ]);

        return back()->with('success', 'Affectation modifiée.');
    }

    public function destroyAssignment(PedagogicalAssignment $assignment, SchoolYearGuardService $schoolYearGuard)
    {
        $schoolYearGuard->assertNotLocked($assignment->schoolYear);

        $assignment->delete();

        return back()->with('success', 'Affectation supprimée.');
    }

    public function storePeriod(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $data = $request->validate(['school_year_id' => ['required', 'exists:school_years,id'], 'name' => ['required', 'string', 'max:100'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after_or_equal:starts_at'], 'grade_entry_starts_at' => ['nullable', 'date'], 'grade_entry_ends_at' => ['nullable', 'date', 'after_or_equal:grade_entry_starts_at']]);
        $schoolYearGuard->assertNotLocked(SchoolYear::findOrFail($data['school_year_id']));
        $data['code'] = Str::slug($data['name'], '_');
        $data['position'] = AcademicPeriod::where('school_year_id', $data['school_year_id'])->max('position') + 1;
        AcademicPeriod::updateOrCreate(['school_year_id' => $data['school_year_id'], 'code' => $data['code']], $data);
        return back()->with('success', 'Période enregistrée.');
    }

    public function togglePeriod(AcademicPeriod $period, SchoolYearGuardService $schoolYearGuard)
    {
        $schoolYearGuard->assertNotLocked($period->schoolYear);

        $isOpen = ! $period->grade_entry_open;
        $period->update(['grade_entry_open' => $isOpen, 'status' => $isOpen ? 'open' : 'closed']);
        return back()->with('success', 'Accès de saisie mis à jour.');
    }

    public function storeEvaluationType(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:evaluation_types,name'], 'default_coefficient' => ['required', 'numeric', 'min:0.1'], 'default_scale' => ['required', 'integer', 'in:10,20,40,100']]);
        $data['code'] = Str::slug($data['name'], '_');
        $data['position'] = EvaluationType::max('position') + 1;
        EvaluationType::create($data);
        return back()->with('success', 'Type d’évaluation ajouté.');
    }

    public function updateSettings(Request $request, SchoolYear $schoolYear, SchoolYearGuardService $schoolYearGuard)
    {
        $schoolYearGuard->assertNotLocked($schoolYear);

        $data = $request->validate(['organization_mode' => ['required', 'in:trimesters,semesters'], 'default_scale' => ['required', 'integer', 'in:10,20,40,100'], 'minimum_grade' => ['required', 'numeric', 'min:0'], 'allow_decimals' => ['nullable', 'boolean'], 'decimal_places' => ['required', 'integer', 'min:0', 'max:2'], 'allow_appreciations' => ['nullable', 'boolean'], 'allow_edit_after_submission' => ['nullable', 'boolean'], 'administrative_validation_required' => ['nullable', 'boolean'], 'lock_after_validation' => ['nullable', 'boolean']]);
        foreach (['allow_decimals', 'allow_appreciations', 'allow_edit_after_submission', 'administrative_validation_required', 'lock_after_validation'] as $key) { $data[$key] = $request->boolean($key); }
        GradeSetting::updateOrCreate(['school_year_id' => $schoolYear->id], $data);
        return back()->with('success', 'Règles de notes enregistrées.');
    }

    /**
     * Création explicite d'une matière avec son coefficient de base — jusqu'ici une
     * matière n'existait qu'en sous-produit d'un autre formulaire (nouvelle affectation
     * ou configuration de coefficient par cycle), sans jamais pouvoir fixer son
     * coefficient de base au moment de la création.
     */
    public function storeMatiere(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100', 'unique:matieres,nom'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:99.9'],
            'bareme' => ['nullable', 'numeric', 'min:1', 'max:999.99'],
        ], [
            'nom.required' => 'Saisissez le nom de la matière.',
            'nom.unique' => 'Cette matière existe déjà.',
            'coefficient.required' => 'Indiquez le coefficient de la matière.',
        ]);

        Matiere::create($data);

        return back()->with('success', "Matière « {$data['nom']} » créée avec un coefficient de {$data['coefficient']}.");
    }

    public function updateMatiere(Request $request, Matiere $matiere)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('matieres', 'nom')->ignore($matiere->id)],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:99.9'],
            // Barème de base par défaut (repli si aucun barème par cycle n'est configuré
            // pour l'année en cours, voir GradeCalculationService::resolveBareme()).
            'bareme' => ['nullable', 'numeric', 'min:1', 'max:999.99'],
        ], [
            'nom.required' => 'Saisissez le nom de la matière.',
            'nom.unique' => 'Cette matière existe déjà.',
            'coefficient.required' => 'Indiquez le coefficient de la matière.',
        ]);

        $matiere->update($data);

        return back()->with('success', "Matière « {$matiere->nom} » modifiée.");
    }

    /**
     * Une matière déjà utilisée (note saisie, affectation pédagogique, configuration de
     * coefficient/barème par cycle) ne peut pas être supprimée : la retirer casserait
     * silencieusement ces données existantes plutôt que de simplement empêcher un usage
     * futur — même principe que Classroom::destroy() (bloqué s'il y a des inscriptions).
     */
    public function destroyMatiere(Matiere $matiere)
    {
        if ($matiere->notes()->exists()) {
            return back()->withErrors(['matiere' => "Impossible de supprimer « {$matiere->nom} » : des notes y sont déjà rattachées."]);
        }

        if ($matiere->pedagogicalAssignments()->exists()) {
            return back()->withErrors(['matiere' => "Impossible de supprimer « {$matiere->nom} » : des affectations pédagogiques y sont rattachées."]);
        }

        if ($matiere->configurations()->exists()) {
            return back()->withErrors(['matiere' => "Impossible de supprimer « {$matiere->nom} » : des configurations de coefficient/barème y sont rattachées."]);
        }

        $nom = $matiere->nom;
        $matiere->delete();

        return back()->with('success', "Matière « {$nom} » supprimée.");
    }

    public function storeSubjectConfiguration(Request $request, SchoolYearGuardService $schoolYearGuard)
    {
        $data = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'matiere_id' => ['nullable', 'exists:matieres,id'],
            'subject_name' => ['nullable', 'string', 'max:100'],
            'cycle' => ['nullable', 'string'],
            // Série du lycée (ex. L, S, ES) : n'a de sens que si cycle=lycee, voir
            // GradeCalculationService::resolveCoefficient() pour la priorité de résolution
            // (série exacte > cycle sans distinction de série > tous cycles).
            'serie' => ['nullable', 'string', 'max:50'],
            'coefficient' => ['required', 'numeric', 'min:0.1'],
            // Barème (système "sunuBulletin" du primaire, ex. Mathématiques /80) :
            // uniquement pertinent pour le cycle primaire, voir GradeCalculationService::
            // resolveBareme()/usesBaremeSystem(). Laissé vide, la matière reste sur le
            // système standard (coefficient + note /20).
            'bareme' => ['nullable', 'numeric', 'min:1', 'max:999.99'],
        ]);
        $schoolYearGuard->assertNotLocked(SchoolYear::findOrFail($data['school_year_id']));
        // Accès via ?? plutôt que direct : "matiere_id" est absent de $data (pas seulement
        // null) quand le formulaire envoie subject_name à la place — un accès direct
        // déclenche une ErrorException PHP ("Undefined array key"), jamais rencontrée tant
        // que le formulaire imposait toujours un matiere_id (select sans option vide).
        if (! ($data['matiere_id'] ?? null) && blank($data['subject_name'] ?? null)) {
            return back()->withErrors(['subject_name' => 'Sélectionnez une matière ou saisissez-en une nouvelle.']);
        }
        $data['matiere_id'] ??= Matiere::firstOrCreate(['nom' => trim($data['subject_name'])], ['coefficient' => $data['coefficient']])->id;
        unset($data['subject_name']);
        $data['cycle'] = $data['cycle'] ?? null;
        $data['serie'] = $data['cycle'] === 'lycee' ? ($data['serie'] ?? null) : null;
        SubjectConfiguration::updateOrCreate(['school_year_id' => $data['school_year_id'], 'matiere_id' => $data['matiere_id'], 'cycle' => $data['cycle'], 'serie' => $data['serie'], 'classroom_id' => null], $data);
        return back()->with('success', 'Coefficient de matière enregistré.');
    }
}
