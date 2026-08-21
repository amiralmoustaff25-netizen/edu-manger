<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\TimetableEntry;
use App\Support\TimetableGrid;

class TimetableGridService
{
    /**
     * Grille [jour][créneau] => contenu (chaîne vide si non saisi) pour une classe, sur son
     * année scolaire. Utilisée par TimetableController (édition/import/PDF admin) et par les
     * pages élève/parent en lecture seule — une seule implémentation de la requête.
     */
    public function grid(Classroom $classroom, ?SchoolYear $schoolYear = null): array
    {
        $schoolYear ??= $classroom->schoolYear ?? SchoolYear::getActive();

        $existing = $schoolYear
            ? TimetableEntry::where('classroom_id', $classroom->id)->where('school_year_id', $schoolYear->id)->get()
            : collect();

        $grid = [];
        foreach (TimetableGrid::DAYS as $day) {
            foreach (TimetableGrid::SLOTS as $slot) {
                $grid[$day][$slot] = '';
            }
        }
        foreach ($existing as $entry) {
            $grid[$entry->day][$entry->slot] = $entry->content ?? '';
        }

        return $grid;
    }
}
