<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Statuts explicites du cycle de vie d'une année scolaire, avec les transitions
 * valides entre eux. Centralise la règle métier pour éviter les changements de statut
 * incohérents (ex: archiver une année qui n'a jamais été clôturée).
 */
class SchoolYearStatus
{
    public const PREPARATION = 'preparation';

    public const ACTIVE = 'active';

    public const CLOSING = 'closing';

    public const CLOSED = 'closed';

    public const ARCHIVED = 'archived';

    public const ALL = [
        self::PREPARATION,
        self::ACTIVE,
        self::CLOSING,
        self::CLOSED,
        self::ARCHIVED,
    ];

    /**
     * CLOSING -> ACTIVE : annulation d'une clôture en cours (anomalie bloquante détectée).
     * CLOSED -> ACTIVE : réouverture exceptionnelle par le Super Admin.
     */
    public const TRANSITIONS = [
        self::PREPARATION => [self::ACTIVE],
        self::ACTIVE => [self::CLOSING],
        self::CLOSING => [self::ACTIVE, self::CLOSED],
        self::CLOSED => [self::ARCHIVED, self::ACTIVE],
        self::ARCHIVED => [],
    ];

    /** Ancien vocabulaire (avant l'extension du cycle de vie à 5 états) -> nouveau. */
    public const LEGACY_MAPPING = [
        'upcoming' => self::PREPARATION,
        'active' => self::ACTIVE,
        'completed' => self::CLOSED,
    ];

    /**
     * Convertit les valeurs de statut existantes de l'ancien vocabulaire vers le nouveau.
     * Utilisé par la migration 2026_08_11_150000_extend_school_years_lifecycle et testé
     * directement (voir SchoolYearLifecycleTest) sans dupliquer cette logique.
     */
    public static function migrateLegacyValues(): void
    {
        foreach (self::LEGACY_MAPPING as $legacy => $current) {
            DB::table('school_years')->where('status', $legacy)->update(['status' => $current]);
        }
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function isTerminal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }

    public static function labels(): array
    {
        return [
            self::PREPARATION => 'Préparation',
            self::ACTIVE => 'Active',
            self::CLOSING => 'En cours de clôture',
            self::CLOSED => 'Clôturée',
            self::ARCHIVED => 'Archivée',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }
}
