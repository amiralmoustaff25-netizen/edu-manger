<?php

namespace App\Services;

use App\Models\ProgramAnnual;
use Illuminate\Support\Facades\Cache;

class CahierTexteService
{
    public function __construct(private ProgramProgressService $programProgressService) {}

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

        // Volumes horaires réel/prévu : délègue à ProgramProgressService (source de vérité
        // déjà utilisée par le tableau de bord pédagogique) plutôt que de dupliquer le calcul
        // ici — cette méthode renvoyait auparavant 0/0 en dur, jamais les vraies valeurs.
        $metrics = $this->programProgressService->metrics($program);

        return [
            'global' => $global,
            'chapters' => $chapters,
            'volume_realise' => $metrics['realised_hours'],
            'volume_prevu' => $metrics['planned_hours'],
        ];
    }

    public function invalidateCache(ProgramAnnual $program): void
    {
        Cache::forget("program.{$program->id}.progress");
    }
}
