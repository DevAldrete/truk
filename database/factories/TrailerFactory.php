<?php

namespace Database\Factories;

use App\Models\Trailer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trailer>
 */
class TrailerFactory extends Factory
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
            'name' => 'Remolque '.fake()->unique()->numberBetween(1, 999),
            'plate' => strtoupper(fake()->unique()->bothify('???-##-##')),
            'configuration' => fake()->randomElement(['Caja seca', 'Plataforma', 'Refrigerado', 'Tanque', 'Portacontenedores']),
            'max_payload_grams' => fake()->numberBetween(500, 30000) * 1000,
            'max_volume_cm3' => fake()->optional()->numberBetween(10, 100) * 1000000,
        ];
    }
}
