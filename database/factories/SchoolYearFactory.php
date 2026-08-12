<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use App\Support\SchoolYearStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolYearFactory extends Factory
{
    protected $model = SchoolYear::class;

    protected static int $yearCounter = 2020;

    public function definition(): array
    {
        $year = static::$yearCounter++;

        if ($year > 2099) {
            $year = static::$yearCounter = 2020;
        }

        return [
            'year_string' => "{$year}-".($year + 1),
            'start_date' => "{$year}-09-01",
            'end_date' => ($year + 1).'-06-30',
            'is_active' => false,
            'status' => SchoolYearStatus::PREPARATION,
        ];
    }
}
