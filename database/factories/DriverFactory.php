<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carrier_party_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'license_number' => fake()->unique()->numerify('#########'),
            'license_expires_at' => fake()->optional()->dateTimeBetween('now', '+3 years'),
        ];
    }
}
