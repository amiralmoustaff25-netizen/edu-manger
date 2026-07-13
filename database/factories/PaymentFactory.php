<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'amount' => $this->faker->numberBetween(50000, 150000),
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'mobile_money', 'check']),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed', 'refunded']),
            'reference' => 'PAY-' . date('y') . '-' . strtoupper($this->faker->unique()->bothify('??????')),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
