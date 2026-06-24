<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager-comptable', 'comptable']);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['manager-comptable', 'comptable']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager-comptable', 'comptable']);
    }

    public function validatePartial(User $user): bool
    {
        return $user->hasRole('manager-comptable');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasRole('manager-comptable');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole('manager-comptable');
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
