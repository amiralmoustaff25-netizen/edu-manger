<?php

namespace Database\Factories;

use App\Models\Matiere;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatiereFactory extends Factory
{
    protected $model = Matiere::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->unique()->randomElement(['Mathématiques', 'Français', 'Sciences', 'Histoire', 'Géographie']),
        ];
    }
}
