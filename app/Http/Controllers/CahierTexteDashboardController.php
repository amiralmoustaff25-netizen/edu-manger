<?php

namespace App\Http\Controllers;

use App\Models\ChapterCompletion;
use App\Models\Classroom;
use App\Models\ProgramAnnual;
use App\Services\SchoolYearContext;
use App\Support\UserRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CahierTexteDashboardController extends Controller
{
    public function index(Request $request, SchoolYearContext $context): View
    {
        $viewingYear = $context->current();

        // Scopé à l'année consultée : les programmes annuels ne sont jamais
        // dupliqués d'une année à l'autre (repartent volontairement vierges à
        // chaque nouvelle année scolaire), donc sans ce filtre ce tableau de
        // bord mélangeait indéfiniment tous les programmes jamais créés.
        $query = ProgramAnnual::query()->with(['classroom', 'subject', 'teacher'])
            ->when($viewingYear, fn ($query) => $query->forSchoolYear($viewingYear->id));

        if (! UserRoles::isPedagogicalOverseer($request->user())) {
            $query->forTeacher($request->user()->id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $programs = $query->latest()->get();

        // Le filtre "Classe" du tableau de bord ne doit lister que les classes du
        // professeur (mêmes affectations que $programs ci-dessus), pas tout
        // l'établissement comme pour l'admin/super-admin/surveillant.
        $classroomsQuery = Classroom::query()
            ->when($viewingYear, fn ($query) => $query->where('school_year_id', $viewingYear->id));

        if (! UserRoles::isPedagogicalOverseer($request->user())) {
            $classroomIdsWithProgram = ProgramAnnual::forTeacher($request->user()->id)->pluck('classroom_id');

            $classroomsQuery->where(function ($query) use ($request, $classroomIdsWithProgram) {
                $query->where('teacher_id', $request->user()->id)
                    ->orWhereIn('id', $classroomIdsWithProgram);
            });
        }

        $classrooms = $classroomsQuery->orderBy('name')->get();
        $selectedClassroomId = $request->input('classroom_id');

        return view('cahier-textes.dashboard', compact('programs', 'classrooms', 'selectedClassroomId'));
    }

    public function progress(ProgramAnnual $program): JsonResponse
    {
        $this->authorize('view', $program);

        $progress = $program->progressPercentage;

        return response()->json([
            'global' => $progress,
            'chapters' => $program->chapters()->get()->map(fn ($chapter) => [
                'id' => $chapter->id,
                'titre' => $chapter->titre,
                'progress' => $chapter->volume_horaire_prevu > 0 ? round(($chapter->volume_horaire_realise / $chapter->volume_horaire_prevu) * 100, 2) : 0,
            ]),
        ]);
    }

    /**
     * Volume horaire de chapitres cochés (ChapterCompletion) par mois sur les 12
     * derniers mois, pour ce programme. Auparavant un stub qui renvoyait toujours des
     * zéros (aucun graphique réel ne pouvait donc jamais s'afficher).
     */
    public function timeline(ProgramAnnual $program): JsonResponse
    {
        $this->authorize('view', $program);

        $chapterIds = $program->chapters()->pluck('id');

        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = ucfirst($month->translatedFormat('M Y'));

            $data[] = (float) ChapterCompletion::whereIn('program_chapter_id', $chapterIds)
                ->whereYear('date_traitement', $month->year)
                ->whereMonth('date_traitement', $month->month)
                ->join('program_chapters', 'chapter_completions.program_chapter_id', '=', 'program_chapters.id')
                ->sum('program_chapters.volume_horaire_prevu');
        }

        return response()->json(['labels' => $labels, 'data' => $data]);
    }
}
