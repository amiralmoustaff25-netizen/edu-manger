<?php

namespace App\Policies;

use App\Models\ProgramAnnual;
use App\Models\User;

class ProgramAnnualPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('voir-programmes');
    }

    public function view(User $user, ProgramAnnual $program): bool
    {
        return $user->can('voir-programmes') && ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasRole('surveillant') || $program->teacher_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('creer-programme');
    }

    public function update(User $user, ProgramAnnual $program): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return $program->teacher_id === $user->id && $program->isModifiable();
    }

    public function delete(User $user, ProgramAnnual $program): bool
    {
        return $user->hasRole('super-admin') || ($user->hasRole('admin') && ! in_array($program->status, ['valide_surveillant', 'valide_directeur', 'verrouille'], true));
    }

    public function validateSurveillant(User $user, ProgramAnnual $program): bool
    {
        return $user->can('valider-programme-surveillant') && $program->status === 'soumis';
    }

    public function validateDirecteur(User $user, ProgramAnnual $program): bool
    {
        return $user->can('valider-programme-directeur') && $program->status === 'valide_surveillant';
    }

    public function reject(User $user, ProgramAnnual $program): bool
    {
        return $user->can('rejeter-programme') && in_array($program->status, ['soumis', 'valide_surveillant'], true);
    }
}
