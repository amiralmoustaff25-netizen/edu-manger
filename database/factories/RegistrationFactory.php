<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
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
            'school_year_id' => SchoolYear::factory(),
            'registration_fee_paid' => $this->faker->numberBetween(10000, 50000),
            'monthly_fee' => $this->faker->numberBetween(50000, 150000),
            'registration_date' => $this->faker->date(),
            'academic_year' => $this->faker->year(),
            'matricule' => 'ELE-'.date('y').'-'.str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => $this->faker->randomElement(['active', 'pending']),
        ];
    }
}
