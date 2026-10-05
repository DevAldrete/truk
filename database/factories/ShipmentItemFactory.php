<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'order_item_id' => null,
            'description' => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 500),
            'unit' => 'piece',
            'weight_grams' => fake()->numberBetween(100, 500000),
            'volume_cm3' => fake()->numberBetween(100, 1000000),
            'hazmat' => false,
        ];
    }
}
