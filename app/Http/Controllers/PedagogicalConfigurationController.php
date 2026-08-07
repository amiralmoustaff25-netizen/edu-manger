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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedagogicalConfigurationController extends Controller
{
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

        $issues = [
            ['label' => 'Classes sans affectation pédagogique', 'count' => $classrooms->filter(fn ($classroom) => ! $assignments->contains('classroom_id', $classroom->id))->count(), 'tab' => 'assignments'],
            ['label' => 'Professeurs sans affectation', 'count' => Teacher::whereDoesntHave('pedagogicalAssignments', fn ($query) => $query->where('school_year_id', $schoolYear->id)->where('is_active', true))->count(), 'tab' => 'assignments'],
            ['label' => 'Matières sans coefficient configuré', 'count' => Matiere::whereDoesntHave('configurations', fn ($query) => $query->where('school_year_id', $schoolYear->id)->where('is_active', true))->count(), 'tab' => 'subjects'],
            ['label' => 'Périodes non configurées', 'count' => $periods->isEmpty() ? 1 : 0, 'tab' => 'periods'],
        ];

        return view('pedagogical-configuration.index', compact('schoolYears', 'schoolYear', 'assignments', 'classrooms', 'configuredSubjects', 'periods', 'issues'));
    }

    public function assignments(Request $request)
    {
        $schoolYears = SchoolYear::orderByDesc('year_string')->get();
        $schoolYear = $request->filled('school_year_id') ? SchoolYear::findOrFail($request->integer('school_year_id')) : SchoolYear::getActive();
        $query = PedagogicalAssignment::with(['teacher.user', 'classroom', 'matiere', 'schoolYear'])->latest('updated_at');
        if ($schoolYear) { $query->where('school_year_id', $schoolYear->id); }
        foreach (['teacher_id', 'classroom_id', 'matiere_id'] as $filter) { if ($request->filled($filter)) { $query->where($filter, $request->integer($filter)); } }

        // N'afficher que les 5 dernières affectations réalisées pour améliorer la lisibilité.
        return view('pedagogical-configuration.assignments', [
            'assignments' => $query->take(5)->get(), 'schoolYear' => $schoolYear, 'schoolYears' => $schoolYears,
            'teachers' => Teacher::with('user')->orderBy('matricule')->get(), 'classrooms' => $schoolYear ? Classroom::where('school_year_id', $schoolYear->id)->orderBy('name')->get() : collect(), 'matieres' => Matiere::orderBy('nom')->get(),
        ]);
    }

    public function storeAssignments(Request $request)
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

        $volumeErrors = [];
        foreach ($data['classroom_ids'] as $classroomId) {
            $volume = $request->input("classroom_volumes.$classroomId");
            if ($volume === null || $volume === '' || ! is_numeric($volume) || $volume < 0 || $volume > 50) {
                $volumeErrors["classroom_volumes.$classroomId"] = 'Indiquez un volume entre 0 et 50 heures pour chaque classe sélectionnée.';
            }
        }
        if ($volumeErrors !== []) {
            return back()->withInput()->withErrors($volumeErrors);
        }

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

        $created = 0;
        DB::transaction(function () use ($data, $request, $teacher, $subjectIds, &$created) {
            foreach ($data['classroom_ids'] as $classroomId) {
                foreach ($subjectIds as $matiereId) {
                    $assignment = PedagogicalAssignment::firstOrCreate(['teacher_id' => $teacher->id, 'classroom_id' => $classroomId, 'matiere_id' => $matiereId, 'school_year_id' => $data['school_year_id']], ['volume_horaire_hebdo' => $request->input("classroom_volumes.$classroomId"), 'is_active' => true]);
                    if (! $assignment->wasRecentlyCreated) {
                        $assignment->update(['volume_horaire_hebdo' => $request->input("classroom_volumes.$classroomId"), 'is_active' => true]);
                    }
                    if ($assignment->wasRecentlyCreated) { $created++; }
                }
            }
        });

        return redirect()->route('pedagogical-configuration.assignments', ['school_year_id' => $data['school_year_id']])->with('success', "$created affectation(s) pédagogique(s) créée(s).");
    }

    public function toggleAssignment(PedagogicalAssignment $assignment)
    {
        $isActive = ! $assignment->is_active;
        $assignment->update(['is_active' => $isActive, 'deactivated_at' => $isActive ? null : now(), 'deactivated_by' => $isActive ? null : auth()->id()]);
        return back()->with('success', 'Statut de l’affectation mis à jour.');
    }

    public function storePeriod(Request $request)
    {
        $data = $request->validate(['school_year_id' => ['required', 'exists:school_years,id'], 'name' => ['required', 'string', 'max:100'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after_or_equal:starts_at'], 'grade_entry_starts_at' => ['nullable', 'date'], 'grade_entry_ends_at' => ['nullable', 'date', 'after_or_equal:grade_entry_starts_at']]);
        $data['code'] = Str::slug($data['name'], '_');
        $data['position'] = AcademicPeriod::where('school_year_id', $data['school_year_id'])->max('position') + 1;
        AcademicPeriod::updateOrCreate(['school_year_id' => $data['school_year_id'], 'code' => $data['code']], $data);
        return back()->with('success', 'Période enregistrée.');
    }

    public function togglePeriod(AcademicPeriod $period)
    {
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

    public function updateSettings(Request $request, SchoolYear $schoolYear)
    {
        $data = $request->validate(['organization_mode' => ['required', 'in:trimesters,semesters'], 'default_scale' => ['required', 'integer', 'in:10,20,40,100'], 'minimum_grade' => ['required', 'numeric', 'min:0'], 'allow_decimals' => ['nullable', 'boolean'], 'decimal_places' => ['required', 'integer', 'min:0', 'max:2'], 'allow_appreciations' => ['nullable', 'boolean'], 'allow_edit_after_submission' => ['nullable', 'boolean'], 'administrative_validation_required' => ['nullable', 'boolean'], 'lock_after_validation' => ['nullable', 'boolean']]);
        foreach (['allow_decimals', 'allow_appreciations', 'allow_edit_after_submission', 'administrative_validation_required', 'lock_after_validation'] as $key) { $data[$key] = $request->boolean($key); }
        GradeSetting::updateOrCreate(['school_year_id' => $schoolYear->id], $data);
        return back()->with('success', 'Règles de notes enregistrées.');
    }

    public function storeSubjectConfiguration(Request $request)
    {
        $data = $request->validate(['school_year_id' => ['required', 'exists:school_years,id'], 'matiere_id' => ['nullable', 'exists:matieres,id'], 'subject_name' => ['nullable', 'string', 'max:100'], 'cycle' => ['nullable', 'string'], 'coefficient' => ['required', 'numeric', 'min:0.1']]);
        if (! $data['matiere_id'] && blank($data['subject_name'] ?? null)) {
            return back()->withErrors(['subject_name' => 'Sélectionnez une matière ou saisissez-en une nouvelle.']);
        }
        $data['matiere_id'] ??= Matiere::firstOrCreate(['nom' => trim($data['subject_name'])], ['coefficient' => $data['coefficient']])->id;
        unset($data['subject_name']);
        SubjectConfiguration::updateOrCreate(['school_year_id' => $data['school_year_id'], 'matiere_id' => $data['matiere_id'], 'cycle' => $data['cycle'] ?? null, 'classroom_id' => null], $data);
        return back()->with('success', 'Coefficient de matière enregistré.');
    }
}
