<?php

namespace Database\Seeders;

use App\Models\Matiere;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
use Illuminate\Database\Seeder;

/**
 * Disciplines réelles du primaire (modèle "sunuBulletin" fourni par l'école),
 * chacune avec son propre barème plutôt qu'un coefficient sur note /20 — voir
 * GradeCalculationService::resolveBareme()/usesBaremeSystem().
 *
 * Idempotent : peut être relancé sans dupliquer ni écraser un barème déjà
 * personnalisé manuellement (matières identifiées par nom, configurations par
 * matière + année scolaire + cycle=primaire).
 */
class PrimaireDisciplinesSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::where('is_active', true)->first();

        if (! $schoolYear) {
            $this->command?->error('Aucune année scolaire active : impossible de configurer les barèmes du primaire.');

            return;
        }

        $disciplines = [
            ['name' => 'Langue et communication', 'bareme' => 70],
            ['name' => 'Mathématiques', 'bareme' => 80],
            ['name' => 'Découverte du monde', 'bareme' => 40],
            ['name' => 'Développement durable', 'bareme' => 40],
            ['name' => 'Récitation / Chant', 'bareme' => 10],
            ['name' => 'Éducation artistique', 'bareme' => 10],
            ['name' => 'Lecture', 'bareme' => 10],
            ['name' => 'Arabe', 'bareme' => 10],
        ];

        foreach ($disciplines as $discipline) {
            $matiere = Matiere::firstOrCreate(
                ['nom' => $discipline['name']],
                ['coefficient' => 1]
            );

            SubjectConfiguration::updateOrCreate(
                [
                    'matiere_id' => $matiere->id,
                    'school_year_id' => $schoolYear->id,
                    'cycle' => 'primaire',
                    'classroom_id' => null,
                ],
                [
                    'coefficient' => 1,
                    'bareme' => $discipline['bareme'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Barèmes primaire (sunuBulletin) configurés pour l\'année '.$schoolYear->year_string.'.');
    }
}
