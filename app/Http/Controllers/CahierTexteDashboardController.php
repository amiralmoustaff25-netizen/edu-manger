<?php

namespace App\Http\Controllers;

use App\Models\ProgramAnnual;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CahierTexteDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProgramAnnual::query()->with(['classroom', 'subject', 'teacher']);

        if (! $request->user()->hasRole(['super-admin', 'admin', 'surveillant'])) {
            $query->forTeacher($request->user()->id);
        }

        $programs = $query->latest()->get();

        return view('cahier-textes.dashboard', compact('programs'));
    }

    public function progress(ProgramAnnual $program): \Illuminate\Http\JsonResponse
    {
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

    public function timeline(ProgramAnnual $program): \Illuminate\Http\JsonResponse
    {
        $labels = [];
        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $month = now()->subMonths(11 - $i)->translatedFormat('M');
            $labels[] = ucfirst($month);
            $data[] = 0;
        }

        return response()->json(['labels' => $labels, 'data' => $data]);
    }
}
