<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StopShipment>
 */
class StopShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stop_id' => Stop::factory(),
            'shipment_id' => Shipment::factory(),
        ];
    }
}
