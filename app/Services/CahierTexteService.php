<?php

namespace App\Services;

use App\Models\ProgramAnnual;
use Illuminate\Support\Facades\Cache;

class CahierTexteService
{
    public function computeProgress(ProgramAnnual $program): array
    {
        $global = $program->progressPercentage;
        $chapters = [];
        foreach ($program->chapters()->get() as $chapter) {
            $chapters[] = [
                'id' => $chapter->id,
                'titre' => $chapter->titre,
                'progress' => $chapter->volume_horaire_prevu > 0 ? round(($chapter->volume_horaire_realise / $chapter->volume_horaire_prevu) * 100, 2) : 0,
            ];
        }

        return [
            'global' => $global,
            'chapters' => $chapters,
            'volume_realise' => 0,
            'volume_prevu' => 0,
        ];
    }

    public function invalidateCache(ProgramAnnual $program): void
    {
        Cache::forget("program.{$program->id}.progress");
    }
}
