<?php

namespace App\Policies;

use App\Models\Parent;
use App\Models\User;

class ParentPolicy
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
     * Détermine si l'utilisateur peut voir la liste des parents.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut voir les détails d'un parent spécifique.
     */
    public function view(User $user, ParentModel $parent): bool
    {
        // Un parent peut voir son propre profil
        if ($user->role === 'parent' && $user->id === $parent->user_id) {
            return true;
        }

        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut créer un parent.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut modifier un parent.
     */
    public function update(User $user, ParentModel $parent): bool
    {
        // Un parent peut modifier son propre profil
        if ($user->role === 'parent' && $user->id === $parent->user_id) {
            return true;
        }

        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut supprimer un parent.
     */
    public function delete(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut restaurer un parent supprimé.
     */
    public function restore(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement un parent.
     */
    public function forceDelete(User $user, ParentModel $parent): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Détermine si l'utilisateur peut archiver un parent.
     */
    public function archive(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut associer un élève à un parent.
     */
    public function attachStudent(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut dissocier un élève d'un parent.
     */
    public function detachStudent(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut réinitialiser le mot de passe d'un parent.
     */
    public function resetPassword(User $user, ParentModel $parent): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Détermine si l'utilisateur peut voir les enfants d'un parent.
     * Un parent ne peut voir que ses propres enfants.
     */
    public function viewStudents(User $user, ParentModel $parent): bool
    {
        // Un parent peut voir ses propres enfants
        if ($user->role === 'parent' && $user->id === $parent->user_id) {
            return true;
        }

        return $user->hasAnyRole(['super-admin', 'admin']);
    }
}
