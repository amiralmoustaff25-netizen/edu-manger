<?php

namespace App\Support;

/**
 * Types d'évaluation utilisables lors de la saisie de notes, selon le cycle de la
 * classe. Le primaire n'évalue qu'en "composition" (pas de devoirs notés) ; le
 * collège et le lycée ont les deux. Centralisé ici pour que la règle soit identique
 * entre les vues (options du menu déroulant) et la validation serveur — avant, seule
 * la vue appliquait la restriction, un envoi direct du formulaire pouvait la contourner.
 */
class EvaluationTypeScope
{
    public const LABELS = [
        'devoir' => 'Devoir',
        'composition' => 'Composition',
    ];

    public static function allowedFor(string $cycle): array
    {
        return in_array($cycle, ['college', 'lycee'], true)
            ? ['devoir', 'composition']
            : ['composition'];
    }
}
