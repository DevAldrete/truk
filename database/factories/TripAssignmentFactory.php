<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripAssignment>
 */
class TripAssignmentFactory extends Factory
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
            'driver_id' => null,
            'vehicle_id' => null,
            'trailer_id' => null,
            'assigned_at' => now(),
            'released_at' => null,
        ];
    }
}
