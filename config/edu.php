<?php

return [
    'volume_horaire_hebdomadaire_maximum' => 18,

    // En primaire, une classe a UN seul professeur principal qui couvre toutes les
    // matières générales (pas de volume horaire par matière à saisir, contrairement au
    // secondaire) — sauf ces matières spécialisées, qui peuvent avoir leur propre
    // professeur dédié en plus du principal (comparaison insensible à la casse/accents,
    // voir PedagogicalConfigurationController::isSpecialistSubject()).
    'primary_specialist_subjects' => ['anglais', 'musique'],
    'overpayment_mode' => 'change', // 'change' (rendre la monnaie) ou 'credit' (créditer l'élève)
    'school_months' => ['Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet'],

    // Changement de mot de passe obligatoire à la première connexion (EnsurePasswordChanged).
    // Le code n'est jamais retiré : les comptes continuent d'être créés avec
    // password_must_change=true, seule l'application de la redirection est désactivable ici.
    // À remettre à true avant tout passage en production.
    'force_password_change' => env('FORCE_PASSWORD_CHANGE', true),

    // Mot de passe temporaire fixe utilisé par un admin lors d'une réinitialisation
    // (UserController::resetPassword, ParentController::resetPassword) : plus facile à
    // communiquer par téléphone/papier à un parent ou professeur qu'un mot de passe
    // aléatoire. Le compte reste forcé à le changer à la prochaine connexion
    // (password_must_change=true), tant que force_password_change ci-dessus est actif.
    'default_reset_password' => env('DEFAULT_RESET_PASSWORD', 'password123'),

    // Nombre maximum d'évaluations notées par matière et par période, collège/lycée
    // uniquement (le primaire n'a qu'une composition par matière et par période, voir
    // EvaluationTypeScope::allowedFor()) — voir GradeController pour l'application de
    // cette limite. Configurable pour pouvoir évoluer sans toucher au code.
    'max_evaluations_per_period' => [
        'devoir' => 2,
        'composition' => 1,
    ],
];
