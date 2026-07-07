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
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->hasPermissionTo('supprimer-classe');
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
