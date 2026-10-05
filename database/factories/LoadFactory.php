<?php

namespace Database\Factories;

use App\Enums\LoadStatus;
use App\Models\Load;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Load>
 */
class LoadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'LOAD-'.fake()->unique()->numerify('#####'),
            'status' => LoadStatus::Draft->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
