<?php

namespace Database\Factories;

use App\Models\DeliveryAttempt;
use App\Models\DeliveryAttemptLine;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAttemptLine>
 */
class DeliveryAttemptLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_attempt_id' => DeliveryAttempt::factory(),
            'shipment_id' => Shipment::factory(),
            'shipment_item_id' => null,
            'quantity' => fake()->numberBetween(0, 20),
            'success' => true,
            'discrepancy_reason' => null,
            'notes' => null,
        ];
    }
}
