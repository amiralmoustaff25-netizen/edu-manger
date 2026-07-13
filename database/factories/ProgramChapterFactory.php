<?php

namespace Database\Factories;

use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramChapterFactory extends Factory
{
    protected $model = ProgramChapter::class;

    public function definition(): array
    {
        return [
            'program_annual_id' => ProgramAnnual::factory(),
            'parent_id' => null,
            'ordre' => 1,
            'type' => $this->faker->randomElement(['chapitre', 'lecon', 'sous_partie']),
            'titre' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'volume_horaire_prevu' => 1.5,
            'volume_horaire_realise' => 0,
        ];
    }
}
