<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'address_1' => fake()->streetAddress,
            'city' => fake()->randomElement(['Dallas', 'Forney', 'Sunnyvale', 'Richardson']),
            'state' => 'TX',
            'zip_code' => '75' . mt_rand(100, 999)
        ];
    }
}
