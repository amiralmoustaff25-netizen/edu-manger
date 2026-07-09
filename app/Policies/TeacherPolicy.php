<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('voir-professeurs');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->can('voir-professeurs');
    }

    public function create(User $user): bool
    {
        return $user->can('creer-professeur');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->can('modifier-professeur');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->can('supprimer-professeur');
    }

    public function manageRib(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }
}
