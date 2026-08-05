<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('voir-paiements');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('voir-paiements');
    }

    public function create(User $user): bool
    {
        return $user->can('enregistrer-paiement');
    }

    public function validatePartial(User $user): bool
    {
        return $user->can('valider-paiement-partiel');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('modifier-paiement');
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Règle métier : un paiement validé (complet ou partiel validé) ne peut jamais
        // être supprimé physiquement, uniquement annulé via cancel() (traçabilité obligatoire).
        if ($payment->isValidated()) {
            return false;
        }

        return $user->can('supprimer-paiement');
    }

    /**
     * Annulation d'un paiement validé avec motif et traçabilité (remplace la suppression).
     */
    public function cancel(User $user, Payment $payment): bool
    {
        if ($payment->isCancelled()) {
            return false;
        }

        return $user->can('annuler-paiement');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
