<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolYearFactory extends Factory
{
    protected $model = SchoolYear::class;

    public function definition(): array
    {
        $year = rand(2020, 2030);

        return [
            'year_string' => "{$year}-".($year + 1),
            'start_date' => "{$year}-09-01",
            'end_date' => ($year + 1).'-06-30',
            'is_active' => false,
            'status' => 'upcoming',
        ];
    }
}
