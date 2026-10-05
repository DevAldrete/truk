<?php

namespace Database\Factories;

use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Models\Stop;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stop>
 */
class StopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'sequence' => 0,
            'type' => fake()->randomElement(StopType::values()),
            'location_id' => null,
            'location_snapshot' => null,
            'planned_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'status' => StopStatus::Pending->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
