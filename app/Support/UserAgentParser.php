<?php

namespace App\Support;

/**
 * Extraction heuristique (regex) de navigateur/OS/type d'appareil depuis un
 * User-Agent brut, pour l'affichage dans le Journal des connexions. Volontairement
 * léger plutôt qu'une dépendance externe (ex. jenssegers/agent) : la précision
 * exigée ici est celle d'un tableau de bord de sécurité lisible par un humain,
 * pas une détection exhaustive de toutes les variantes de navigateurs existantes.
 */
class UserAgentParser
{
    public static function browser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/Edg\//', $userAgent) => 'Edge',
            (bool) preg_match('/OPR\//', $userAgent) => 'Opera',
            (bool) preg_match('/Chrome\//', $userAgent) && ! str_contains($userAgent, 'Chromium') => 'Chrome',
            (bool) preg_match('/Firefox\//', $userAgent) => 'Firefox',
            (bool) preg_match('/Version\/.*Safari\//', $userAgent) => 'Safari',
            (bool) preg_match('/MSIE|Trident\//', $userAgent) => 'Internet Explorer',
            default => 'Autre',
        };
    }

    public static function platform(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/Windows/', $userAgent) => 'Windows',
            (bool) preg_match('/Mac OS X/', $userAgent) && ! preg_match('/iPhone|iPad/', $userAgent) => 'macOS',
            (bool) preg_match('/Android/', $userAgent) => 'Android',
            (bool) preg_match('/iPhone|iPad|iOS/', $userAgent) => 'iOS',
            (bool) preg_match('/Linux/', $userAgent) => 'Linux',
            default => 'Autre',
        };
    }

    public static function deviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/iPad|Tablet/', $userAgent)) {
            return 'tablette';
        }

        if (preg_match('/Mobi|iPhone|Android.*Mobile/', $userAgent)) {
            return 'mobile';
        }

        return 'ordinateur';
    }
}
