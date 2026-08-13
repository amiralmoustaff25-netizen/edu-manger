<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Passe-droit : super-admin a accès à tout (cohérent avec LoginLogPolicy).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste du journal d'audit.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('voir-journal-audit');
    }

    /**
     * Voir le détail d'une entrée du journal d'audit.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('voir-detail-journal-audit');
    }

    /**
     * Le journal d'audit est écrit uniquement par AuditLogService, jamais
     * manuellement via l'UI.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Une entrée d'audit ne doit jamais pouvoir être modifiée après coup —
     * l'intégrité du journal est la garantie même de son utilité en cas
     * d'incident. Aucune permission ne peut débloquer cette action.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Même raisonnement que update() : un journal d'audit purgeable par
     * l'application elle-même ne prouve plus rien. Pas de route/permission
     * associée à cette capacité.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
