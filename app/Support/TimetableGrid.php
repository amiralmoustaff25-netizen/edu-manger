<?php

namespace App\Support;

/**
 * Grille horaire fixe (mêmes jours/créneaux pour toutes les classes primaire) — pas de
 * configuration par école pour l'instant, voir échange avec l'utilisateur du 2026-08-21.
 */
class TimetableGrid
{
    public const DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    public const SLOTS = [
        '08h/9h', '9h/10h', '10h/11h', '11h/11h30', '11h30/12h',
        '12h/13h', '13h/15h', '15h/16h', '16h/17h', '17h/18h',
    ];
}
