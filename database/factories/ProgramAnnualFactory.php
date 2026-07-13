<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\ProgramAnnual;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramAnnualFactory extends Factory
{
    protected $model = ProgramAnnual::class;

    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Matiere::factory(),
            'teacher_id' => User::factory(),
            'school_year_id' => SchoolYear::factory(),
            'status' => 'brouillon',
        ];
    }
}
