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
        if ($this->canTarget(auth()->user(), $target)) {
            return;
        }

        $message = $action
            ? "Seul un super-administrateur peut {$action} un compte super-administrateur."
            : 'Seul un super-administrateur peut agir sur un compte super-administrateur.';

        abort(403, $message);
    }

    /**
     * Version booléenne de la même règle, pour les cas qui doivent l'évaluer sans lever
     * d'exception (affichage conditionnel dans une vue, autorisation d'un Form Request) :
     * un compte super-admin ne peut être ciblé (modifié, désactivé, dépossédé...) que par
     * un autre super-admin. Un compte qui n'est pas super-admin est toujours une cible
     * valide, quel que soit l'acteur.
     */
    public function canTarget(?User $actor, User $target): bool
    {
        if (! $target->hasRole('super-admin')) {
            return true;
        }

        return (bool) $actor?->hasRole('super-admin');
    }
}
