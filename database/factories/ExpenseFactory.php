<?php

namespace Database\Factories;

use App\Enums\ExpenseType;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'driver_id' => null,
            'type' => ExpenseType::Fuel->value,
            'amount_minor' => fake()->numberBetween(10000, 500000),
            'currency' => 'MXN',
            'incurred_at' => now(),
            'vendor' => fake()->optional()->company(),
            'notes' => null,
            'liters_ml' => fake()->numberBetween(20000, 200000),
            'price_per_liter_minor' => fake()->numberBetween(2000, 3000),
            'odometer_meters' => null,
            'tank' => null,
            'receipt_path' => null,
            'idempotency_key' => (string) Str::uuid(),
            'recorded_by' => null,
        ];
    }
}
