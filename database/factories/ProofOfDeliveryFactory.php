<?php

namespace Database\Factories;

use App\Models\ProofOfDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProofOfDelivery>
 */
class ProofOfDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_attempt_id' => null,
            'stop_id' => null,
            'shipment_id' => null,
            'recipient_name' => fake()->optional()->name(),
            'signature_path' => null,
            'photos' => null,
            'document_paths' => null,
            'consent' => true,
            'notes' => fake()->optional()->sentence(),
            'latitude' => null,
            'longitude' => null,
            'captured_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
            'recorded_by' => null,
        ];
    }
}
