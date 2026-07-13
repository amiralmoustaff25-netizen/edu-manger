<?php

namespace App\Support;

class ProgramStatus
{
    public const TRANSITIONS = [
        'brouillon' => ['soumis', 'rejete'],
        'soumis' => ['valide_surveillant', 'rejete'],
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
