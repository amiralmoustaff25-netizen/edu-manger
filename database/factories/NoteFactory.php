<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'matiere_id' => Matiere::factory(),
            'valeur' => $this->faker->numberBetween(0, 20),
            'type_evaluation' => $this->faker->randomElement(['devoir', 'interrogation', 'examen', 'tp']),
            'periode' => $this->faker->randomElement(['trimestre_1', 'trimestre_2', 'trimestre_3']),
            'appreciation' => $this->faker->optional()->sentence(),
        ];
    }
}
