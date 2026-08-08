<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Voir ClassroomFeePolicy — même correctif de cohérence architecturale, même absence de
 * changement de comportement (permission plate déjà vérifiée avant, identique après).
 */
class InvoicePolicy
{
    public function modifierFacture(User $user, Invoice $invoice): bool
    {
        return $user->can('modifier-facture');
    }

    public function supprimerFacture(User $user, Invoice $invoice): bool
    {
        return $user->can('supprimer-facture');
    }
}
