<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::firstOrCreate([
            'year_string' => '2025-2026',
        ], [
            'is_active' => true,
            'status' => 'active',
        ]);

        $teacher = User::firstOrCreate([
            'matricule' => 'PROF-DEMO',
        ], [
            'name' => 'Professeur Démo',
            'email' => 'prof.demo@edumanager.sn',
            'password' => bcrypt('password'),
            'role' => 'professeur',
            'is_active' => true,
            'password_must_change' => false,
        ]);
        $teacher->syncRoles(['professeur']);

        $classrooms = [
            ['name' => 'CM1 A', 'subject' => 'Mathématiques'],
            ['name' => '6ème A', 'subject' => 'Français'],
            ['name' => 'CM2 A', 'subject' => 'Sciences'],
        ];

        foreach ($classrooms as $entry) {
            $classroom = Classroom::firstOrCreate([
                'name' => $entry['name'],
                'school_year_id' => $schoolYear->id,
            ], ['cycle' => 'primaire', 'teacher_id' => $teacher->id]);
            $subject = Matiere::firstOrCreate(['nom' => $entry['subject']]);

            $program = ProgramAnnual::firstOrCreate([
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
                'school_year_id' => $schoolYear->id,
            ], [
                'teacher_id' => $teacher->id,
                'status' => 'brouillon',
            ]);

            $chapters = [];
            for ($i = 1; $i <= 5; $i++) {
                $chapter = ProgramChapter::firstOrCreate([
                    'program_annual_id' => $program->id,
                    'titre' => 'Chapitre '.$i,
                    'type' => 'chapitre',
                ], [
                    'ordre' => $i,
                    'volume_horaire_prevu' => 2.5,
                    'description' => 'Chapitre de démo',
                ]);
                $chapters[] = $chapter;

                for ($j = 1; $j <= 2; $j++) {
                    $lesson = ProgramChapter::firstOrCreate([
                        'program_annual_id' => $program->id,
                        'parent_id' => $chapter->id,
                        'titre' => 'Leçon '.$j,
                        'type' => 'lecon',
                    ], [
                        'ordre' => $j,
                        'volume_horaire_prevu' => 1.0,
                        'description' => 'Leçon de démo',
                    ]);

                    for ($k = 1; $k <= 2; $k++) {
                        ProgramChapter::firstOrCreate([
                            'program_annual_id' => $program->id,
                            'parent_id' => $lesson->id,
                            'titre' => 'Sous-partie '.$k,
                            'type' => 'sous_partie',
                        ], [
                            'ordre' => $k,
                            'volume_horaire_prevu' => 0.5,
                            'description' => 'Sous-partie de démo',
                        ]);
                    }
                }
            }
        }
    }
}
