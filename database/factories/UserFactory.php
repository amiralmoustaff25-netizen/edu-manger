<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // unique() (pas random_int() seul) : sans garantie d'unicité, une collision sur
        // matricule (contrainte unique en base) devenait statistiquement probable au fil
        // d'une suite de tests créant des centaines d'utilisateurs — même défaut que
        // SchoolYearFactory::$yearCounter avant correctif.
        $uniqueId = $this->faker->unique()->numberBetween(1, 99999);

        return [
            'matricule' => sprintf('MAT-%05d', $uniqueId),
            'name' => 'Utilisateur '.$uniqueId,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
