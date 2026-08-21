<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Un élève peut voir ses propres informations, le personnel habilité peut voir tout élève,
     * un parent peut voir uniquement ses propres enfants.
     *
     * Nommée `voirDetailEleve` (et non `view`) car Laravel convertit l'ability
     * 'voir-detail-eleve' en méthode via Str::camel() avant de résoudre la policy
     * (voir Gate::formatAbilityToMethod) : le nom doit correspondre exactement.
     */
    public function voirDetailEleve(User $user, User $student): bool
    {
        if ($user->id === $student->id) {
            return true;
        }

        if ($user->hasAnyRole(['super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant', 'professeur'])) {
            return true;
        }

        if ($user->hasRole('parent') && optional($user->parentProfile)->students()->where('users.id', $student->id)->exists()) {
            return true;
        }

        return $user->hasPermissionTo('voir-detail-eleve');
    }
}
