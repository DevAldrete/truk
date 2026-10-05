<?php

namespace Database\Factories;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'code' => 'PKG-'.fake()->unique()->numerify('#######'),
            'status' => PackageStatus::Created->value,
            'weight_grams' => fake()->optional()->numberBetween(100, 50000),
        ];
    }
}
