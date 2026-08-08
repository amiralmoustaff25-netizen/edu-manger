<?php

namespace App\Policies;

use App\Models\ClassroomFee;
use App\Models\User;

/**
 * ClassroomFeeController::authorize() passait une instance de ClassroomFee sans qu'aucune
 * policy ne soit enregistrée pour ce modèle : l'instance était silencieusement ignorée,
 * seule la permission plate comptait (identique au comportement ci-dessous). Cette policy
 * ne change donc rien au comportement actuel — elle rend explicite ce qui se passait déjà,
 * et prépare le terrain pour une future règle réellement liée à l'instance si besoin.
 */
class ClassroomFeePolicy
{
    public function modifierFraisClasse(User $user, ClassroomFee $classroomFee): bool
    {
        return $user->can('modifier-frais-classe');
    }

    public function supprimerFraisClasse(User $user, ClassroomFee $classroomFee): bool
    {
        return $user->can('supprimer-frais-classe');
    }
}
