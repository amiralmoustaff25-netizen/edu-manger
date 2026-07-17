<?php

namespace App\Policies;

use App\Models\User;

class StudentPhotoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function upload(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }

    public function remove(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }
}
