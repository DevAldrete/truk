<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Mexican cities used to build coherent test addresses.
     *
     * @var array<int, array{city: string, state: string}>
     */
    protected static array $places = [
        ['city' => 'Guadalajara', 'state' => 'Jalisco'],
        ['city' => 'Monterrey', 'state' => 'Nuevo León'],
        ['city' => 'Ciudad de México', 'state' => 'Ciudad de México'],
        ['city' => 'Puebla', 'state' => 'Puebla'],
        ['city' => 'Querétaro', 'state' => 'Querétaro'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $place = fake()->randomElement(static::$places);

        return [
            'party_id' => null,
            'name' => fake()->randomElement(['Bodega', 'CEDIS', 'Planta', 'Sucursal']).' '.fake()->city(),
            'street' => 'Av. '.fake()->streetName(),
            'exterior_number' => (string) fake()->numberBetween(1, 2500),
            'interior_number' => null,
            'neighborhood' => 'Col. '.fake()->lastName(),
            'city' => $place['city'],
            'state' => $place['state'],
            'postal_code' => fake()->numerify('#####'),
            'references' => fake()->optional()->sentence(),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'timezone' => fake()->optional()->randomElement([
                'America/Mexico_City',
                'America/Monterrey',
                'America/Chihuahua',
                'America/Tijuana',
            ]),
        ];
    }
}
