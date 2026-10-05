<?php

namespace Database\Factories;

use App\Enums\ScanType;
use App\Models\Package;
use App\Models\ScanEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScanEvent>
 */
class ScanEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'shipment_id' => null,
            'trip_id' => null,
            'stop_id' => null,
            'type' => ScanType::Loaded->value,
            'occurred_at' => now(),
            'latitude' => null,
            'longitude' => null,
            'idempotency_key' => (string) Str::uuid(),
            'recorded_by' => null,
            'notes' => null,
        ];
    }
}
