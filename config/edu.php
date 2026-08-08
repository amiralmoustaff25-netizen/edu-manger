<?php

return [
    'volume_horaire_hebdomadaire_maximum' => 18,
    'overpayment_mode' => 'change', // 'change' (rendre la monnaie) ou 'credit' (créditer l'élève)
    'school_months' => ['Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet'],

    // Changement de mot de passe obligatoire à la première connexion (EnsurePasswordChanged).
    // Le code n'est jamais retiré : les comptes continuent d'être créés avec
    // password_must_change=true, seule l'application de la redirection est désactivable ici.
    // À remettre à true avant tout passage en production.
    'force_password_change' => env('FORCE_PASSWORD_CHANGE', true),
];
