<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'excused']),
            'notes' => $this->faker->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
