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
        // Le compteur statique persiste sur toute la durée du run de tests (il n'est
        // pas réinitialisé par RefreshDatabase). L'ancien code réassignait $year en même
        // temps que static::$yearCounter lors du rebouclage 2099→2020, ce qui renvoyait
        // la même année à deux appels consécutifs — deux SchoolYear créés coup sur coup
        // (ex. dans un même setUp()) au moment du rebouclage obtenaient alors le même
        // year_string, violant sa contrainte unique.
        $year = static::$yearCounter;
        static::$yearCounter++;

        if (static::$yearCounter > 2099) {
            static::$yearCounter = 2020;
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
