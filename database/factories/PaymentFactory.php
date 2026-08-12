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
            'amount' => $this->faker->numberBetween(5000, 150000),
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['espèces', 'virement', 'chèque', 'mobile_money']),
            'payment_type' => $this->faker->randomElement(['mensualite', 'inscription', 'cantine', 'transport', 'internat', 'autre']),
            'status' => $this->faker->randomElement(['complet', 'partiel']),
            'month' => $this->faker->randomElement(['Septembre', 'Octobre', 'Novembre', 'Décembre', null]),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
