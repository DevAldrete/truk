<?php

namespace Database\Factories;

use App\Enums\PartyType;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PartyType::Customer->value,
            'name' => fake()->company(),
            'legal_name' => fake()->optional()->company(),
            'rfc' => null,
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->numerify('##########'),
        ];
    }

    /**
     * Indicate that the party is of the given type.
     */
    public function type(PartyType $type): static
    {
        return $this->state(fn (): array => ['type' => $type->value]);
    }
}
