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
        return $user->can('supprimer-paiement');
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
