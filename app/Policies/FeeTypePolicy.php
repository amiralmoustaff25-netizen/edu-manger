<?php

namespace App\Policies;

use App\Models\FeeType;
use App\Models\User;

/**
 * Voir ClassroomFeePolicy — même correctif de cohérence architecturale, même absence de
 * changement de comportement (permission plate déjà vérifiée avant, identique après).
 */
class FeeTypePolicy
{
    public function modifierTypeFrais(User $user, FeeType $feeType): bool
    {
        return $user->can('modifier-type-frais');
    }

    public function supprimerTypeFrais(User $user, FeeType $feeType): bool
    {
        return $user->can('supprimer-type-frais');
    }
}
