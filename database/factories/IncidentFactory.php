<?php

namespace Database\Factories;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => null,
            'stop_id' => null,
            'shipment_id' => null,
            'driver_id' => null,
            'type' => fake()->randomElement(IncidentType::values()),
            'severity' => IncidentSeverity::Medium->value,
            'status' => IncidentStatus::Open->value,
            'description' => fake()->sentence(),
            'occurred_at' => now(),
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'idempotency_key' => (string) Str::uuid(),
            'reported_by' => null,
        ];
    }
}
