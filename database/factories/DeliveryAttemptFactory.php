<?php

namespace Database\Factories;

use App\Enums\DeliveryOutcome;
use App\Models\DeliveryAttempt;
use App\Models\Stop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeliveryAttempt>
 */
class DeliveryAttemptFactory extends Factory
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
            'shipment_id' => null,
            'outcome' => DeliveryOutcome::Delivered->value,
            'failure_reason' => null,
            'recipient_name' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
            'latitude' => null,
            'longitude' => null,
            'occurred_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
            'recorded_by' => null,
        ];
    }
}
