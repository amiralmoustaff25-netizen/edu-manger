<?php

namespace App\Support;

/**
 * Statuts explicites du cycle de vie d'une inscription élève, avec les transitions
 * valides entre eux. Centralise la règle métier pour éviter les changements de statut
 * incohérents (ex: passer directement de "pending" à "graduated").
 */
class StudentStatus
{
    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';

    public const TRANSFERRED = 'transferred';

    public const GRADUATED = 'graduated';

    public const WITHDRAWN = 'withdrawn';

    public const EXPELLED = 'expelled';

    public const CANCELLED = 'cancelled';

    public const ALL = [
        self::PENDING,
        self::ACTIVE,
        self::SUSPENDED,
        self::TRANSFERRED,
        self::GRADUATED,
        self::WITHDRAWN,
        self::EXPELLED,
        self::CANCELLED,
    ];

    /** Statuts terminaux : plus aucune transition n'est possible depuis ces états. */
    public const TRANSITIONS = [
        self::PENDING => [self::ACTIVE, self::CANCELLED],
        self::ACTIVE => [self::SUSPENDED, self::TRANSFERRED, self::GRADUATED, self::WITHDRAWN, self::EXPELLED],
        self::SUSPENDED => [self::ACTIVE, self::WITHDRAWN, self::EXPELLED],
        self::TRANSFERRED => [],
        self::GRADUATED => [],
        self::WITHDRAWN => [],
        self::EXPELLED => [],
        self::CANCELLED => [],
    ];

    /** Statuts pour lesquels un motif écrit est obligatoire (traçabilité disciplinaire/administrative). */
    public const REQUIRES_REASON = [
        self::SUSPENDED,
        self::WITHDRAWN,
        self::EXPELLED,
        self::CANCELLED,
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function requiresReason(string $status): bool
    {
        return in_array($status, self::REQUIRES_REASON, true);
    }

    public static function isTerminal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }

    public static function labels(): array
    {
        return [
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::SUSPENDED => 'Suspendu',
            self::TRANSFERRED => 'Transféré',
            self::GRADUATED => 'Diplômé',
            self::WITHDRAWN => 'Retiré',
            self::EXPELLED => 'Exclu',
            self::CANCELLED => 'Annulé',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }
}
