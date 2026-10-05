<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 weeks');

        return [
            'number' => 'TRP-'.fake()->unique()->numerify('#####'),
            'status' => TripStatus::Planned->value,
            'planned_start_at' => $start,
            'planned_end_at' => (clone $start)->modify('+8 hours'),
            'timezone' => 'America/Mexico_City',
            'driver_id' => null,
            'vehicle_id' => null,
            'trailer_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
