<?php

namespace App\Services;

use App\Models\User;

class SuperAdminProtectionService
{
    /**
     * Vérifie que l'utilisateur authentifié est autorisé à agir sur un compte
     * super-administrateur. Seul un super-administrateur peut agir sur un autre
     * compte super-administrateur (modification, suppression, changement de
     * rôle, etc.).
     */
    public function ensureCanTarget(User $target, ?string $action = null): void
    {
        if (! $target->hasRole('super-admin')) {
            return;
        }

        $message = $action
            ? "Seul un super-administrateur peut {$action} un compte super-administrateur."
            : 'Seul un super-administrateur peut agir sur un compte super-administrateur.';

        abort_unless(auth()->user()?->hasRole('super-admin'), 403, $message);
    }
}
