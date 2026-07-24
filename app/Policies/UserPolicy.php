<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Un élève peut voir ses propres informations, le personnel habilité peut voir tout élève.
     */
    public function view(User $user, User $student): bool
    {
        if ($user->id === $student->id) {
            return true;
        }

        if ($user->hasAnyRole(['super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant', 'professeur'])) {
            return true;
        }

        return $user->hasPermissionTo('voir-detail-eleve');
    }

}
