<?php

namespace App\Policies;

use App\Models\LoginLog;
use App\Models\User;

class LoginLogPolicy
{
    /**
     * Passe-droit : super-admin a accès à tout.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des logs de connexion.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('voir-logs-connexion');
    }

    /**
     * Voir les détails d'un log de connexion.
     */
    public function view(User $user, LoginLog $loginLog): bool
    {
        return $user->can('voir-detail-log-connexion');
    }

    /**
     * Les logs de connexion sont créés automatiquement, pas manuellement.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Les logs de connexion ne peuvent pas être modifiés.
     */
    public function update(User $user, LoginLog $loginLog): bool
    {
        return false;
    }

    /**
     * Supprimer un log de connexion.
     */
    public function delete(User $user, LoginLog $loginLog): bool
    {
        return $user->can('supprimer-log-connexion');
    }

    /**
     * Restaurer un log de connexion supprimé.
     */
    public function restore(User $user, LoginLog $loginLog): bool
    {
        return $user->can('restaurer-log-connexion');
    }

    /**
     * Supprimer définitivement un log de connexion.
     */
    public function forceDelete(User $user, LoginLog $loginLog): bool
    {
        return $user->can('supprimer-definitivement-log-connexion');
    }
}
