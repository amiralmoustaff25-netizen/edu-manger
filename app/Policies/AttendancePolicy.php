<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return null;
    }

    // SEC-02 : la vue d'ensemble /attendances n'avait aucun contrôle propre,
    // uniquement le middleware de route (role:super-admin|admin, retiré depuis).
    public function viewAny(User $user): bool
    {
        return $user->can('voir-presences');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        // Un professeur peut voir une présence s'il enseigne dans la classe
        if ($user->hasRole('professeur')) {
            return $user->classrooms()->where('classrooms.id', $attendance->classroom_id)->exists();
        }

        return false;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        // Un professeur peut modifier une présence s'il enseigne dans la classe
        // et si la date n'est pas trop ancienne (moins de 7 jours)
        if ($user->hasRole('professeur')) {
            $isAssigned = $user->classrooms()->where('classrooms.id', $attendance->classroom_id)->exists();
            $isRecent = $attendance->date >= now()->subDays(7)->format('Y-m-d');

            return $isAssigned && $isRecent;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('professeur');
    }
}
