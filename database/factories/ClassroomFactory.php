<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['CP', 'CE1', 'CE2', 'CM1', 'CM2']).' '.fake()->randomLetter(),
            'cycle' => fake()->randomElement(['primaire', 'college', 'lycee']),
            'school_year_id' => SchoolYear::factory(),
            'teacher_id' => User::factory(),
        ];
    }
}
