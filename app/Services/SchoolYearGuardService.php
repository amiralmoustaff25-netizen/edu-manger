<?php

namespace App\Services;

use App\Models\SchoolYear;
use Illuminate\Validation\ValidationException;

/**
 * Centralise la règle métier de verrouillage des années scolaires clôturées.
 * Toute écriture (inscription, paiement, grille tarifaire, etc.) rattachée à une
 * année scolaire clôturée doit passer par ce garde-fou, sauf pour un super-admin
 * qui peut exceptionnellement outrepasser le verrou pour corriger une erreur.
 */
class SchoolYearGuardService
{
    public function assertNotLocked(?SchoolYear $schoolYear): void
    {
        if (! $schoolYear || ! $schoolYear->isLocked()) {
            return;
        }

        if (auth()->user()?->hasRole('super-admin')) {
            return;
        }

        throw ValidationException::withMessages([
            'school_year' => "L'année scolaire {$schoolYear->year_string} est clôturée et ne peut plus être modifiée.",
        ]);
    }
}
