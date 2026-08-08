<?php

namespace App\Policies;

use App\Models\SchoolYear;
use App\Models\User;

/**
 * Voir ClassroomFeePolicy — même correctif de cohérence architecturale, même absence de
 * changement de comportement (permission plate déjà vérifiée avant, identique après).
 */
class SchoolYearPolicy
{
    public function activerAnneeScolaire(User $user, SchoolYear $schoolYear): bool
    {
        return $user->can('activer-annee-scolaire');
    }

    public function supprimerAnneeScolaire(User $user, SchoolYear $schoolYear): bool
    {
        return $user->can('supprimer-annee-scolaire');
    }
}
