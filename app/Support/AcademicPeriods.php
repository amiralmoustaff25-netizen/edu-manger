<?php

namespace App\Support;

/**
 * Périodes utilisables pour la saisie/consultation des notes, selon le cycle de la
 * classe. Le primaire reste en trimestres (système historique) ; le collège et le
 * lycée fonctionnent en semestres. Centralisé ici pour que la règle soit identique
 * partout (saisie enseignant, bulletins, tableau de bord élève/parent) — même
 * principe que EvaluationTypeScope pour les types d'évaluation.
 */
class AcademicPeriods
{
    public const TRIMESTRES = [
        'trimestre_1' => 'Trimestre 1',
        'trimestre_2' => 'Trimestre 2',
        'trimestre_3' => 'Trimestre 3',
    ];

    public const SEMESTRES = [
        'semestre_1' => 'Semestre 1',
        'semestre_2' => 'Semestre 2',
    ];

    /**
     * @return array<string, string> code => libellé
     */
    public static function forCycle(?string $cycle): array
    {
        return in_array($cycle, ['college', 'lycee'], true) ? self::SEMESTRES : self::TRIMESTRES;
    }

    public static function defaultFor(?string $cycle): string
    {
        return array_key_first(self::forCycle($cycle));
    }

    public static function labelFor(?string $cycle, string $period): string
    {
        return self::forCycle($cycle)[$period] ?? $period;
    }

    public static function isValidFor(?string $cycle, string $period): bool
    {
        return array_key_exists($period, self::forCycle($cycle));
    }
}
