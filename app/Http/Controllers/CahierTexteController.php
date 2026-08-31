<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkToggleRequest;
use App\Http\Requests\StoreChapterCompletionRequest;
use App\Models\ChapterCompletion;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\ProgramHistory;
use App\Services\CahierTexteService;
use App\Support\UserRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CahierTexteController extends Controller
{
    public function __construct(private readonly CahierTexteService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('voir-cahier-textes');

        if (! $request->filled('classroom_id') || ! $request->filled('subject_id')) {
            return redirect()->route('cahier-textes.select');
        }

        // Un professeur ne doit consulter que le cahier de texte d'une classe/matière
        // qui lui est réellement affectée (même contrôle que toggle/bulkToggle/
        // markLessonDone ci-dessous) — sans ceci, il pouvait lire n'importe quelle
        // classe en changeant classroom_id/subject_id dans l'URL.
        if (! UserRoles::isPedagogicalOverseer(auth()->user())) {
            $hasAssignment = PedagogicalAssignment::where('classroom_id', $request->classroom_id)
                ->where('matiere_id', $request->subject_id)
                ->where('is_active', true)
                ->whereHas('teacher', fn ($q) => $q->where('user_id', auth()->id()))
                ->exists();

            // Le titulaire d'une classe de primaire couvre les matières générales même
            // sans PedagogicalAssignment explicite (même règle que GradeController pour
            // la saisie des notes).
            if (! $hasAssignment) {
                $classroom = Classroom::find($request->classroom_id);
                $matiere = Matiere::find($request->subject_id);

                $hasAssignment = $classroom && $matiere
                    && $classroom->teacher_id === auth()->id()
                    && $classroom->cycle === 'primaire'
                    && ! $matiere->isPrimarySpecialistSubject();
            }

            abort_unless($hasAssignment, 403);
        }

        $program = ProgramAnnual::query()
            ->where('classroom_id', $request->classroom_id)
            ->where('subject_id', $request->subject_id)
            ->where('school_year_id', $request->school_year_id ?? now()->year)
            ->first();

        if (! $program) {
            $program = ProgramAnnual::factory()->make();
        }

        return view('cahier-textes.index', compact('program'));
    }

    public function select(): View
    {
        $user = auth()->user();

        if (UserRoles::isPedagogicalOverseer($user)) {
            $classrooms = Classroom::orderBy('name')->get();
            $matieres = Matiere::orderBy('nom')->get();

            return view('cahier-textes.select', compact('classrooms', 'matieres'));
        }

        // Un professeur ne doit voir dans ce sélecteur que ses propres classes/matières
        // affectées — jusqu'ici la vue listait Classroom::all()/Matiere::all(), donc
        // exactement le même menu que l'admin, sans aucun filtre.
        $assignments = PedagogicalAssignment::with(['classroom', 'matiere'])
            ->where('is_active', true)
            ->whereHas('teacher', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $classrooms = $assignments->pluck('classroom')->filter()->unique('id')->values();
        $matieres = $assignments->pluck('matiere')->filter()->unique('id')->values();

        // Titulaire d'une classe de primaire : couvre les matières générales même sans
        // PedagogicalAssignment explicite (même règle que GradeController::index).
        $titulaireClassrooms = Classroom::where('teacher_id', $user->id)
            ->whereNotIn('id', $classrooms->pluck('id'))
            ->get();

        if ($titulaireClassrooms->isNotEmpty()) {
            $classrooms = $classrooms->concat($titulaireClassrooms)->unique('id')->values();

            if ($titulaireClassrooms->contains(fn ($classroom) => $classroom->cycle === 'primaire')) {
                $matieres = $matieres
                    ->concat(Matiere::whereIn('id', Matiere::generalSubjectIds())->get())
                    ->unique('id')
                    ->values();
            }
        }

        return view('cahier-textes.select', compact('classrooms', 'matieres'));
    }

    public function toggle(StoreChapterCompletionRequest $request): JsonResponse
    {
        $chapter = ProgramChapter::findOrFail($request->chapter_id);
        $this->authorize('create', ChapterCompletion::class);
        $program = $chapter->program;

        if ($program->teacher_id !== auth()->id() && ! UserRoles::isPedagogicalOverseer(auth()->user())) {
            abort(403);
        }

        $date = Carbon::parse($request->date)->toDateString();

        return DB::transaction(function () use ($chapter, $date, $program) {
            $existing = ChapterCompletion::where('program_chapter_id', $chapter->id)
                ->whereDate('date_traitement', $date)
                ->first();

            if ($existing) {
                $existing->delete();
                $program->invalidateProgressCache();

                return response()->json(['toggled' => false, 'chapter' => ['id' => $chapter->id, 'completed' => false, 'date' => $date], 'progress' => $this->service->computeProgress($program)]);
            }

            ChapterCompletion::create([
                'program_chapter_id' => $chapter->id,
                'date_traitement' => $date,
                'completed_by' => auth()->id(),
                'remarque' => null,
            ]);
            $program->invalidateProgressCache();
            ProgramHistory::create(['program_annual_id' => $program->id, 'user_id' => auth()->id(), 'action' => 'saisie', 'description' => 'Coche ajoutée', 'metadata' => ['chapter_id' => $chapter->id], 'created_at' => now()]);

            return response()->json(['toggled' => true, 'chapter' => ['id' => $chapter->id, 'completed' => true, 'date' => $date], 'progress' => $this->service->computeProgress($program)]);
        });
    }

    public function bulkToggle(BulkToggleRequest $request): JsonResponse
    {
        $this->authorize('create', ChapterCompletion::class);

        $date = Carbon::parse($request->date)->toDateString();
        $program = ProgramChapter::findOrFail($request->chapter_ids[0])->program;

        if ($program->teacher_id !== auth()->id() && ! UserRoles::isPedagogicalOverseer(auth()->user())) {
            abort(403);
        }

        foreach ($request->chapter_ids as $chapterId) {
            $chapter = ProgramChapter::findOrFail($chapterId);
            // Toutes les cases cochées doivent appartenir au même programme que la
            // première (celle utilisée pour l'autorisation ci-dessus).
            abort_unless($chapter->program_annual_id === $program->id, 422, 'Chapitres appartenant à des programmes différents.');
            $existing = ChapterCompletion::where('program_chapter_id', $chapter->id)->whereDate('date_traitement', $date)->first();
            if ($existing) {
                $existing->delete();
            } else {
                ChapterCompletion::create(['program_chapter_id' => $chapter->id, 'date_traitement' => $date, 'completed_by' => auth()->id()]);
            }
        }

        $program->invalidateProgressCache();

        return response()->json(['ok' => true, 'progress' => $this->service->computeProgress($program)]);
    }

    public function markLessonDone(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'lesson_id' => ['required', 'exists:program_chapters,id'],
        ]);

        $this->authorize('create', ChapterCompletion::class);

        $lesson = ProgramChapter::findOrFail($request->lesson_id);
        $program = $lesson->program;

        if ($program->teacher_id !== auth()->id() && ! UserRoles::isPedagogicalOverseer(auth()->user())) {
            abort(403);
        }

        $chapterIds = collect([$lesson->id])
            ->merge($lesson->children()->pluck('id'));
        $date = Carbon::parse($request->date)->toDateString();

        foreach ($chapterIds as $chapterId) {
            ChapterCompletion::firstOrCreate(
                ['program_chapter_id' => $chapterId, 'date_traitement' => $date],
                ['completed_by' => auth()->id()]
            );
        }

        return response()->json(['ok' => true]);
    }

    public function updateRemark(Request $request, ChapterCompletion $completion): JsonResponse
    {
        $this->authorize('update', $completion);

        $request->validate(['remarque' => ['nullable', 'string', 'max:1000']]);
        $completion->update(['remarque' => $request->remarque]);

        return response()->json(['ok' => true]);
    }
}
