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
        $levels = ['CP', 'CE1', 'CE2', 'CM1', 'CM2'];
        $cycles = ['primaire', 'college', 'lycee'];

        return [
            'name' => $levels[array_rand($levels)].' '.chr(rand(65, 90)),
            'cycle' => $cycles[array_rand($cycles)],
            'school_year_id' => SchoolYear::factory(),
            'teacher_id' => User::factory(),
        ];
    }
}
