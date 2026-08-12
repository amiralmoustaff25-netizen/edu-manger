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

    /**
     * Rôles à fiche dédiée : leur attribution crée des données métier liées
     * (Teacher, Registration, ParentModel) qui ne sont collectées que par leur
     * module dédié, jamais par le formulaire Utilisateurs générique.
     */
    public const DEDICATED_PROFILE_ROLES = ['professeur', 'eleve', 'parent'];

    /**
     * Rôles proposables dans le formulaire d'édition générique pour un compte
     * donné : un rôle à fiche dédiée n'est proposé que si le compte le possède
     * déjà (on peut le conserver, jamais l'attribuer depuis ce formulaire).
     */
    public static function editableRolesFor(User $actor, User $target): array
    {
        $excluded = array_diff(self::DEDICATED_PROFILE_ROLES, [$target->role]);

        return array_values(array_diff(self::assignableBy($actor), $excluded));
    }

    /**
     * Si ce compte a des données métier actives liées à son rôle actuel
     * (affectations pédagogiques, inscription active, enfants liés), retourne
     * le message expliquant pourquoi son rôle ne peut pas être changé ni le
     * compte archivé tant que ces liens n'ont pas été traités depuis le module
     * dédié. Retourne null si le changement/l'archivage est sûr.
     */
    public static function activeBusinessLinkBlockingRoleChange(User $user): ?string
    {
        if ($user->role === 'professeur'
            && $user->teacher?->pedagogicalAssignments()->where('is_active', true)->exists()) {
            return "Ce compte a des affectations pédagogiques actives. Retirez-les depuis la Configuration pédagogique avant de changer son rôle ou d'archiver ce compte.";
        }

        if ($user->role === 'eleve'
            && $user->registrations()->where('status', 'active')->exists()) {
            return "Ce compte élève a une inscription active. Gérez son statut depuis le module Élèves avant de changer son rôle ou d'archiver ce compte.";
        }

        if ($user->role === 'parent'
            && $user->parentProfile && $user->parentProfile->students()->exists()) {
            return "Ce compte parent est lié à au moins un élève. Gérez ses liens depuis le module Parents avant de changer son rôle ou d'archiver ce compte.";
        }

        return null;
    }
}
