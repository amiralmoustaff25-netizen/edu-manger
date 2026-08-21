<?php

namespace App\Policies;

use App\Models\TeacherAttendance;
use App\Models\User;

class TeacherAttendancePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('voir-pointage-enseignants');
    }

    public function view(User $user, TeacherAttendance $attendance): bool
    {
        return $user->can('voir-pointage-enseignants');
    }

    public function create(User $user): bool
    {
        return $user->can('enregistrer-pointage-enseignant');
    }

    public function update(User $user, TeacherAttendance $attendance): bool
    {
        return $user->can('enregistrer-pointage-enseignant');
    }
}
