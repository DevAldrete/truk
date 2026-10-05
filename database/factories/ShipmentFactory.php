<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => null,
            'number' => 'SHP-'.fake()->unique()->numerify('#####'),
            'status' => ShipmentStatus::Planned->value,
            'currency' => 'MXN',
            'pickup_location_id' => null,
            'delivery_location_id' => null,
            'pickup_snapshot' => null,
            'delivery_snapshot' => null,
            'customer_name' => fake()->company(),
            'weight_grams' => fake()->numberBetween(1000, 20000000),
            'volume_cm3' => fake()->numberBetween(1000, 60000000),
            'pieces' => fake()->numberBetween(1, 100),
        ];
    }
}
