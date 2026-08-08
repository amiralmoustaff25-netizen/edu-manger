<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Le "passe-droit" : s'exécute avant toutes les autres vérifications.
     * Si l'utilisateur est super-admin ou admin, il a accès à tout d'office.
     */
    public function before(User $user, string $ability): ?bool
    {
        // 'delete' est exclu du passe-droit : le garde-fou de delete() (aucune inscription
        // rattachée) doit s'appliquer même aux super-admin/admin, sinon un clic malheureux
        // détruit en cascade tout l'historique de paiements/notes/présences de la classe.
        if ($ability === 'delete') {
            return null;
        }

        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return null; // Continue de vérifier les méthodes ci-dessous pour les autres
    }

    /**
     * Détermine si l'utilisateur peut voir la liste des classes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('voir-classes');
    }

    /**
     * Détermine si l'utilisateur peut voir les détails d'une classe spécifique.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('voir-classes') || $user->hasPermissionTo('voir-sa-classe');
    }

    /**
     * Détermine si l'utilisateur peut créer une classe.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('creer-classe');
    }

    /**
     * Détermine si l'utilisateur peut modifier une classe.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('modifier-classe');
    }

    /**
     * Détermine si l'utilisateur peut supprimer une classe.
     *
     * Une classe ayant eu des inscriptions ne peut jamais être supprimée : la suppression
     * SQL réelle cascaderait sur registrations -> payments/invoices/discounts/credits/
     * credit_notes/reminders, notes et attendances, détruisant tout l'historique financier
     * et pédagogique des élèves qui y ont été inscrits, sans confirmation ni possibilité de
     * restauration. Classroom utilise désormais SoftDeletes, mais ce garde-fou reste
     * nécessaire pour ne jamais faire disparaître une classe qui a un historique réel.
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        if ($classroom->registrations()->exists()) {
            return false;
        }

        return $user->hasRole(['super-admin', 'admin']) || $user->hasPermissionTo('supprimer-classe');
    }

    /**
     * Détermine si l'utilisateur peut restaurer une classe supprimée.
     */
    public function restore(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('supprimer-classe');
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement une classe.
     */
    public function forceDelete(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('supprimer-classe');
    }
}
