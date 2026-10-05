<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'description' => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 500),
            'unit' => 'piece',
            'weight_grams' => fake()->numberBetween(100, 500000),
            'volume_cm3' => fake()->numberBetween(100, 1000000),
            'hazmat' => false,
        ];
    }

    /**
     * Indicate that the line carries hazardous material.
     */
    public function hazmat(): static
    {
        return $this->state(fn (): array => ['hazmat' => true]);
    }
}
