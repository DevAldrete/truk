<?php

namespace Database\Factories;

use App\Enums\ComplianceDocumentType;
use App\Models\ComplianceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplianceDocument>
 */
class ComplianceDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ComplianceDocumentType::values()),
            'number' => fake()->optional()->numerify('########'),
            'issued_at' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
            'expires_at' => fake()->optional()->dateTimeBetween('-1 month', '+2 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the document expires in the past.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'issued_at' => now()->subYears(2),
            'expires_at' => now()->subDay(),
        ]);
    }
}
