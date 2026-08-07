<?php

namespace App\Support;

class ProgramStatus
{
    // Workflow simplifié : le professeur soumet son programme (déjà rattaché à une
    // affectation pédagogique classe/matière/professeur), puis un administrateur le
    // valide en une seule étape. L'état "valide_surveillant" est conservé pour les
    // anciens programmes déjà à ce stade, afin qu'ils restent transitionnables.
    public const TRANSITIONS = [
        'brouillon' => ['soumis', 'rejete'],
        'soumis' => ['valide_directeur', 'rejete'],
        'valide_surveillant' => ['valide_directeur', 'rejete'],
        'valide_directeur' => [],
        'rejete' => ['soumis', 'brouillon'],
        'verrouille' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
