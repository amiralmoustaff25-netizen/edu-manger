<?php

namespace App\Imports;

use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProgramsImport implements ToCollection, WithHeadingRow
{
    public function __construct(private readonly int $classroomId, private readonly int $subjectId, private readonly int $schoolYearId)
    {
    }

    public function collection(Collection $rows): void
    {
        $program = ProgramAnnual::create([
            'classroom_id' => $this->classroomId,
            'subject_id' => $this->subjectId,
            'teacher_id' => auth()->id(),
            'school_year_id' => $this->schoolYearId,
        ]);

        foreach ($rows as $row) {
            $chapter = ProgramChapter::create([
                'program_annual_id' => $program->id,
                'parent_id' => null,
                'ordre' => 1,
                'type' => 'chapitre',
                'titre' => $row['chapitre'] ?? 'Chapitre',
                'description' => $row['objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($row['volume_horaire'] ?? 1),
            ]);

            $lesson = ProgramChapter::create([
                'program_annual_id' => $program->id,
                'parent_id' => $chapter->id,
                'ordre' => 1,
                'type' => 'lecon',
                'titre' => $row['lecon'] ?? 'Leçon',
                'description' => $row['objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($row['volume_horaire'] ?? 1),
            ]);

            ProgramChapter::create([
                'program_annual_id' => $program->id,
                'parent_id' => $lesson->id,
                'ordre' => 1,
                'type' => 'sous_partie',
                'titre' => $row['sous-partie'] ?? 'Sous-partie',
                'description' => $row['objectifs'] ?? null,
                'volume_horaire_prevu' => (float) ($row['volume_horaire'] ?? 1),
            ]);
        }
    }
}
