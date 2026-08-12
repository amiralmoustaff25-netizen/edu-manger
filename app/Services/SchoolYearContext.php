<?php

namespace App\Services;

use App\Models\SchoolYear;

/**
 * Résout l'année scolaire actuellement CONSULTÉE (pas forcément l'année active) : un choix
 * en session, sinon l'année active par défaut. Permet à un utilisateur de naviguer les
 * données d'une année passée sans jamais changer l'année active de l'établissement — deux
 * notions volontairement distinctes (voir SchoolYear::getActive() vs current() ici).
 */
class SchoolYearContext
{
    private const SESSION_KEY = 'viewing_school_year_id';

    public function current(): ?SchoolYear
    {
        $id = session(self::SESSION_KEY);

        if ($id) {
            $year = SchoolYear::find($id);

            if ($year) {
                return $year;
            }
        }

        return SchoolYear::getActive();
    }

    public function isViewingActiveYear(): bool
    {
        $current = $this->current();
        $active = SchoolYear::getActive();

        return $current && $active && $current->is($active);
    }

    public function set(SchoolYear $year): void
    {
        session([self::SESSION_KEY => $year->id]);
    }

    public function reset(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
