<?php

namespace App\Policies;

use App\Models\ParentModel;
use App\Models\User;

class ParentPolicy
{
    /**
     * Passe-droit : super-admin a accès à tout.
     * Admin passe aux vérifications de permissions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des parents.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('voir-parents');
    }

    /**
     * Voir les détails d'un parent spécifique.
     */
    public function view(User $user, ParentModel $parent): bool
    {
        // Un parent peut voir son propre profil
        if ($user->hasRole('parent') && $user->id === $parent->user_id) {
            return true;
        }

        return $user->can('voir-detail-parent');
    }

    /**
     * Créer un parent.
     */
    public function create(User $user): bool
    {
        return $user->can('creer-parent');
    }

    /**
     * Modifier un parent.
     */
    public function update(User $user, ParentModel $parent): bool
    {
        // Un parent peut modifier son propre profil
        if ($user->hasRole('parent') && $user->id === $parent->user_id) {
            return true;
        }

        return $user->can('modifier-parent');
    }

    /**
     * Supprimer (soft delete) un parent.
     */
    public function delete(User $user, ParentModel $parent): bool
    {
        return $user->can('archiver-parent');
    }

    /**
     * Restaurer un parent supprimé.
     */
    public function restore(User $user, ParentModel $parent): bool
    {
        return $user->can('restaurer-parent');
    }

    /**
     * Supprimer définitivement un parent.
     */
    public function forceDelete(User $user, ParentModel $parent): bool
    {
        return $user->can('supprimer-parent');
    }

    /**
     * Archiver un parent.
     */
    public function archive(User $user, ParentModel $parent): bool
    {
        return $user->can('archiver-parent');
    }

    /**
     * Associer un élève à un parent.
     */
    public function attachStudent(User $user, ParentModel $parent): bool
    {
        return $user->can('associer-eleve-parent');
    }

    /**
     * Dissocier un élève d'un parent.
     */
    public function detachStudent(User $user, ParentModel $parent): bool
    {
        return $user->can('dissocier-eleve-parent');
    }

    /**
     * Réinitialiser le mot de passe d'un parent.
     */
    public function resetPassword(User $user, ParentModel $parent): bool
    {
        return $user->can('reinitialiser-mot-de-passe-parent');
    }

    /**
     * Voir les enfants d'un parent.
     */
    public function viewStudents(User $user, ParentModel $parent): bool
    {
        if ($user->hasRole('parent') && $user->id === $parent->user_id) {
            return true;
        }

        return $user->can('voir-detail-parent');
    }
}
