<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_party_id' => null,
            'number' => 'ORD-'.fake()->unique()->numerify('#####'),
            'status' => OrderStatus::Draft->value,
            'currency' => 'MXN',
            'requested_pickup_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'requested_delivery_at' => fake()->optional()->dateTimeBetween('+2 weeks', '+1 month'),
            'notes' => fake()->optional()->sentence(),
            'customer_name' => fake()->company(),
            'customer_rfc' => null,
        ];
    }

    /**
     * Indicate that the order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (): array => ['status' => OrderStatus::Confirmed->value]);
    }
}
