<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\PartyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartyContact>
 */
class PartyContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'name' => fake()->name(),
            'position' => fake()->optional()->jobTitle(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('##########'),
        ];
    }
}
