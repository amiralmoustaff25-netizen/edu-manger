<?php

namespace App\Support;

use App\Models\User;

class UserRoles
{
    // Rôles métier du système. Le rôle 'professeur' n'est volontairement pas
    // attribuable via le formulaire utilisateur générique : la fiche professeur
    // exige des informations obligatoires (statut, diplômes...) collectées via
    // le module Professeurs (voir TeacherController::store).
    public const ALL = [
        'super-admin',
        'admin',
        'manager-comptable',
        'comptable',
        'surveillant',
        'professeur',
        'parent',
        'eleve',
    ];

    public const CREATABLE_VIA_USER_FORM = [
        'admin',
        'manager-comptable',
        'comptable',
        'surveillant',
    ];

    /**
     * Rôles qu'un acteur donné est autorisé à attribuer à un compte.
     *
     * Seul un compte super-admin peut attribuer (ou retirer) le rôle
     * super-admin. Spatie court-circuite les Policies dès qu'un acteur possède
     * la permission plate 'modifier-utilisateur' (via Gate::before) : cette
     * restriction doit donc être appliquée explicitement ici, dans la
     * validation, et pas seulement dans une Policy.
     */
    public static function assignableBy(User $actor): array
    {
        if ($actor->hasRole('super-admin')) {
            return self::ALL;
        }

        return array_values(array_diff(self::ALL, ['super-admin']));
    }
}
