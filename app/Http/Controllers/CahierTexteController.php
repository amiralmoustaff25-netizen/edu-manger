<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkToggleRequest;
use App\Http\Requests\StoreChapterCompletionRequest;
use App\Models\ChapterCompletion;
use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\ProgramHistory;
use App\Services\CahierTexteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CahierTexteController extends Controller
{
    public function __construct(private readonly CahierTexteService $service)
    {
    }

    public function index(Request $request): View
    {
        if (! $request->filled('classroom_id') || ! $request->filled('subject_id')) {
            return redirect()->route('cahier-textes.select');
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
        return view('cahier-textes.select');
    }

    public function toggle(StoreChapterCompletionRequest $request): \Illuminate\Http\JsonResponse
    {
        $chapter = ProgramChapter::findOrFail($request->chapter_id);
        $this->authorize('create', ChapterCompletion::class);
        $program = $chapter->program;

        if ($program->teacher_id !== auth()->id() && ! auth()->user()->hasRole(['admin', 'super-admin', 'surveillant'])) {
            abort(403);
        }

        return DB::transaction(function () use ($chapter, $request, $program) {
            $existing = ChapterCompletion::where('program_chapter_id', $chapter->id)
                ->where('date_traitement', $request->date)
                ->first();

            if ($existing) {
                $existing->delete();
                $program->invalidateProgressCache();

                return response()->json(['toggled' => false, 'chapter' => ['id' => $chapter->id, 'completed' => false, 'date' => $request->date], 'progress' => $this->service->computeProgress($program)]);
            }

            ChapterCompletion::create([
                'program_chapter_id' => $chapter->id,
                'date_traitement' => $request->date,
                'completed_by' => auth()->id(),
                'remarque' => null,
            ]);
            $program->invalidateProgressCache();
            ProgramHistory::create(['program_annual_id' => $program->id, 'user_id' => auth()->id(), 'action' => 'saisie', 'description' => 'Coche ajoutée', 'metadata' => ['chapter_id' => $chapter->id], 'created_at' => now()]);

            return response()->json(['toggled' => true, 'chapter' => ['id' => $chapter->id, 'completed' => true, 'date' => $request->date], 'progress' => $this->service->computeProgress($program)]);
        });
    }

    public function bulkToggle(BulkToggleRequest $request): \Illuminate\Http\JsonResponse
    {
        $program = ProgramChapter::findOrFail($request->chapter_ids[0])->program;
        foreach ($request->chapter_ids as $chapterId) {
            $chapter = ProgramChapter::findOrFail($chapterId);
            $existing = ChapterCompletion::where('program_chapter_id', $chapter->id)->where('date_traitement', $request->date)->first();
            if ($existing) {
                $existing->delete();
            } else {
                ChapterCompletion::create(['program_chapter_id' => $chapter->id, 'date_traitement' => $request->date, 'completed_by' => auth()->id()]);
            }
        }

        $program->invalidateProgressCache();

        return response()->json(['ok' => true, 'progress' => $this->service->computeProgress($program)]);
    }

    public function markLessonDone(Request $request, ProgramChapter $lesson): \Illuminate\Http\JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'before_or_equal:today']]);

        $chapterIds = collect([$lesson->id])
            ->merge($lesson->children()->pluck('id'));

        foreach ($chapterIds as $chapterId) {
            ChapterCompletion::firstOrCreate(
                ['program_chapter_id' => $chapterId, 'date_traitement' => $request->date],
                ['completed_by' => auth()->id()]
            );
        }

        return response()->json(['ok' => true]);
    }

    public function updateRemark(Request $request, ChapterCompletion $completion): \Illuminate\Http\JsonResponse
    {
        $request->validate(['remarque' => ['nullable', 'string', 'max:1000']]);
        $completion->update(['remarque' => $request->remarque]);

        return response()->json(['ok' => true]);
    }
}
