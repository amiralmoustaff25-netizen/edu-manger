<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProgramRequest;
use App\Http\Requests\StoreProgramAnnualRequest;
use App\Http\Requests\UpdateProgramAnnualRequest;
use App\Models\AcademicPeriod;
use App\Models\PedagogicalAssignment;
use App\Models\ProgramAnnual;
use App\Models\ProgramHistory;
use App\Support\ProgramStatus;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProgramAnnualController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProgramAnnual::query()->with(['classroom', 'subject', 'teacher', 'chapters']);

        if (! UserRoles::isPedagogicalOverseer($request->user())) {
            $query->forTeacher($request->user()->id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('school_year_id')) {
            $query->forSchoolYear($request->school_year_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $programs = $query->latest()->paginate(20);

        return view('programs.index', compact('programs'));
    }

    public function create(Request $request): View
    {
        $assignments = PedagogicalAssignment::query()
            ->with(['classroom', 'matiere', 'schoolYear', 'teacher.user'])
            ->where('is_active', true)
            ->when(! UserRoles::isPedagogicalOverseer($request->user()), function ($query) use ($request) {
                $query->whereHas('teacher', fn ($teacherQuery) => $teacherQuery->where('user_id', $request->user()->id));
            })
            ->orderBy('classroom_id')
            ->get();
        $periods = AcademicPeriod::query()->orderBy('starts_at')->get();

        $assignmentsMeta = $assignments->mapWithKeys(fn ($assignment) => [
            $assignment->id => [
                'teacher' => $assignment->teacher?->user?->name,
                'matricule' => $assignment->teacher?->matricule,
                'classroom' => $assignment->classroom?->name,
                'matiere' => $assignment->matiere?->nom,
            ],
        ]);

        return view('programs.create', compact('assignments', 'periods', 'assignmentsMeta'));
    }

    public function store(StoreProgramAnnualRequest $request): RedirectResponse
    {
        $this->authorize('create', ProgramAnnual::class);
        $assignment = PedagogicalAssignment::with('teacher')->whereKey($request->integer('pedagogical_assignment_id'))->where('is_active', true)->firstOrFail();

        if (! UserRoles::isPedagogicalOverseer($request->user())) {
            abort_unless($assignment->teacher->user_id === $request->user()->id, 403);
        }

        abort_if(ProgramAnnual::where('classroom_id', $assignment->classroom_id)->where('subject_id', $assignment->matiere_id)->where('school_year_id', $assignment->school_year_id)->exists(), 422, 'Un programme existe déjà pour cette classe, cette matière et cette année scolaire.');

        return DB::transaction(function () use ($request, $assignment) {
            $program = ProgramAnnual::create([
                'pedagogical_assignment_id' => $assignment->id,
                'academic_period_id' => $request->input('academic_period_id'),
                'classroom_id' => $assignment->classroom_id,
                'subject_id' => $assignment->matiere_id,
                'teacher_id' => $assignment->teacher->user_id,
                'school_year_id' => $assignment->school_year_id,
            ]);
            $this->createChapters($program, $request->input('chapters', []));
            $this->recordHistory($program, $request->user(), 'cree', 'Programme créé');

            return redirect()->route('programs.show', $program)->with('success', 'Programme créé avec succès.');
        });
    }

    public function show(ProgramAnnual $program): View
    {
        $this->authorize('view', $program);

        $program->load(['chapters' => fn ($query) => $query->whereNull('parent_id')->with('children.children'), 'history.user']);

        return view('programs.show', compact('program'));
    }

    public function edit(ProgramAnnual $program): View
    {
        $this->authorize('update', $program);
        $program->load('chapters');

        return view('programs.edit', compact('program'));
    }

    public function update(UpdateProgramAnnualRequest $request, ProgramAnnual $program): RedirectResponse
    {
        $this->authorize('update', $program);

        return DB::transaction(function () use ($request, $program) {
            $before = $program->chapters()->get()->toArray();
            $program->chapters()->delete();
            $this->createChapters($program, $request->input('chapters', []));
            $after = $program->fresh()->chapters()->get()->toArray();

            if ($program->wasRecentlyCreated === false && $before !== $after) {
                $this->recordHistory($program, $request->user(), 'modifie', 'Programme modifié', ['before' => $before, 'after' => $after]);
            }

            return redirect()->route('programs.show', $program)->with('success', 'Programme mis à jour.');
        });
    }

    public function submit(ProgramAnnual $program): RedirectResponse
    {
        $this->authorize('update', $program);

        if (! ProgramStatus::canTransition($program->status, 'soumis')) {
            abort(403, 'Transition non autorisée.');
        }

        $program->update(['status' => 'soumis', 'submitted_at' => now()]);
        $this->recordHistory($program, auth()->user(), 'soumis', 'Programme soumis à l’administrateur pour validation');

        return redirect()->back()->with('success', 'Programme soumis avec succès.');
    }

    public function validateDirecteur(ProgramAnnual $program): RedirectResponse
    {
        $this->authorize('validateDirecteur', $program);

        $program->update(['status' => 'valide_directeur', 'validated_by_directeur_id' => auth()->id()]);
        $this->recordHistory($program, auth()->user(), 'valide_directeur', 'Validé par l’administrateur');

        return redirect()->back()->with('success', 'Programme validé par l’administrateur.');
    }

    public function reject(Request $request, ProgramAnnual $program): RedirectResponse
    {
        $this->authorize('reject', $program);
        $request->validate(['motif' => ['required', 'string', 'min:10']]);

        $program->update(['status' => 'rejete']);
        $this->recordHistory($program, auth()->user(), 'rejete', 'Programme rejeté', ['motif' => $request->motif]);

        return redirect()->back()->with('success', 'Programme rejeté.');
    }

    public function destroy(ProgramAnnual $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Programme supprimé.');
    }

    public function importExcel(ImportProgramRequest $request): RedirectResponse
    {
        $this->authorize('create', ProgramAnnual::class);

        // Même source de vérité que store() : la classe/matière/professeur/année viennent
        // de l'affectation pédagogique, jamais d'un identifiant brut envoyé par le client —
        // sinon un professeur pourrait importer un programme sur une classe qu'il n'enseigne
        // pas en modifiant classroom_id/subject_id, et en devenir teacher_id.
        $assignment = PedagogicalAssignment::with('teacher')->whereKey($request->integer('pedagogical_assignment_id'))->where('is_active', true)->firstOrFail();

        if (! UserRoles::isPedagogicalOverseer($request->user())) {
            abort_unless($assignment->teacher->user_id === $request->user()->id, 403);
        }

        abort_if(ProgramAnnual::where('classroom_id', $assignment->classroom_id)->where('subject_id', $assignment->matiere_id)->where('school_year_id', $assignment->school_year_id)->exists(), 422, 'Un programme existe déjà pour cette classe, cette matière et cette année scolaire.');

        $path = $request->file('file')->store('imports');
        $contents = Storage::get($path);

        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($contents)));
        $header = array_map('trim', $rows[0] ?? []);
        $dataRows = array_slice($rows, 1);

        $expectedHeader = ['Chapitre', 'Leçon', 'Sous-partie', 'Titre', 'Objectifs', 'Volume horaire'];
        if ($header === [] || $header === ['']) {
            return back()->withErrors(['file' => 'Le fichier importé est vide ou mal formé.']);
        }

        if ($header !== $expectedHeader) {
            return back()->withErrors(['file' => 'L\'en-tête du fichier CSV est invalide. Colonnes attendues : '.implode(', ', $expectedHeader).'.']);
        }

        $headerCount = count($header);
        foreach ($dataRows as $index => $row) {
            if (count($row) !== $headerCount) {
                return back()->withErrors(['file' => 'La ligne '.($index + 2).' du fichier ne contient pas le bon nombre de colonnes.']);
            }
        }

        // Toutes les lignes doivent être importées ensemble : si l'une d'elles échoue,
        // aucun programme ni chapitre partiel ne doit rester en base.
        $program = DB::transaction(function () use ($request, $assignment, $dataRows, $header) {
            $program = ProgramAnnual::create([
                'pedagogical_assignment_id' => $assignment->id,
                'academic_period_id' => $request->input('academic_period_id'),
                'classroom_id' => $assignment->classroom_id,
                'subject_id' => $assignment->matiere_id,
                'teacher_id' => $assignment->teacher->user_id,
                'school_year_id' => $assignment->school_year_id,
            ]);

            $this->createImportedChapters($program, $dataRows, $header);

            return $program;
        });

        return redirect()->route('programs.show', $program)->with('success', 'Import terminé.');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $path = storage_path('app/public/program-template.csv');
        if (! file_exists($path)) {
            $content = "Chapitre,Leçon,Sous-partie,Titre,Objectifs,Volume horaire\n";
            file_put_contents($path, $content);
        }

        return response()->download($path, 'modele-programme.csv');
    }

    private function createChapters(ProgramAnnual $program, array $items, ?int $parentId = null, int $level = 0): void
    {
        foreach ($items as $index => $item) {
            $chapter = $program->chapters()->create([
                'parent_id' => $parentId,
                'ordre' => $index + 1,
                'type' => $item['type'] ?? 'chapitre',
                'titre' => $item['titre'] ?? 'Sans titre',
                'description' => $item['description'] ?? null,
                'academic_period_id' => $item['academic_period_id'] ?? null,
                'planned_at' => $item['planned_at'] ?? null,
                'status' => $item['status'] ?? 'a_faire',
                'volume_horaire_prevu' => $item['volume_horaire_prevu'] ?? 1,
                'volume_horaire_realise' => 0,
            ]);

            if (! empty($item['children'])) {
                $this->createChapters($program, $item['children'], $chapter->id, $level + 1);
            }
        }
    }

    private function createImportedChapters(ProgramAnnual $program, array $rows, array $header): void
    {
        $chapter = null;
        $lesson = null;
        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            if (! $data) {
                continue;
            }

            $chapter = $program->chapters()->create([
                'parent_id' => null,
                'ordre' => 1,
                'type' => 'chapitre',
                'titre' => $data['Chapitre'] ?? 'Chapitre',
                'description' => $data['Objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($data['Volume horaire'] ?? 1),
            ]);
            $lesson = $program->chapters()->create([
                'parent_id' => $chapter->id,
                'ordre' => 1,
                'type' => 'lecon',
                'titre' => $data['Leçon'] ?? 'Leçon',
                'description' => $data['Objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($data['Volume horaire'] ?? 1),
            ]);
            $program->chapters()->create([
                'parent_id' => $lesson->id,
                'ordre' => 1,
                'type' => 'sous_partie',
                'titre' => $data['Sous-partie'] ?? 'Sous-partie',
                'description' => $data['Objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($data['Volume horaire'] ?? 1),
            ]);
        }
    }

    private function recordHistory(ProgramAnnual $program, $user, string $action, string $description, array $metadata = []): void
    {
        ProgramHistory::create([
            'program_annual_id' => $program->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
