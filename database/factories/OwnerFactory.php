<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Owner>
 */
class OwnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name,
            'address_1' => fake()->streetAddress,
            'city' => fake()->city,
            'state' => fake()->randomElement(['TX', 'FL', 'AL']),
            'zip_code' => (string) fake()->randomNumber(5, true),
            'country' => 'US',
        ];
    }
}
