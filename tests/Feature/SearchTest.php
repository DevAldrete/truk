<?php

use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Party;
use App\Models\Team;
use App\Models\User;
use App\Models\Vehicle;

test('the palette finds parties and sites of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['name' => 'Acme Logistics']);
    Location::factory()->for($team)->create([
        'name' => 'Bodega Norte',
        'city' => 'Monterrey',
        'state' => 'Nuevo León',
    ]);

    $response = $this->actingAs($user)->getJson(route('search', ['current_team' => $team, 'q' => 'ac']));

    $response
        ->assertOk()
        ->assertJsonPath('results.0.type', 'party')
        ->assertJsonPath('results.0.title', 'Acme Logistics');

    $this->actingAs($user)
        ->getJson(route('search', ['current_team' => $team, 'q' => 'bodega']))
        ->assertJsonPath('results.0.type', 'location')
        ->assertJsonPath('results.0.subtitle', 'Monterrey, Nuevo León');
});

test('the palette finds drivers and vehicles of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Driver::factory()->for($team)->create(['name' => 'Juan Pérez', 'license_number' => 'ABC123456']);
    Vehicle::factory()->for($team)->create([
        'name' => 'Unidad 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Torton',
    ]);

    $this->actingAs($user)
        ->getJson(route('search', ['current_team' => $team, 'q' => 'juan']))
        ->assertJsonPath('results.0.type', 'driver')
        ->assertJsonPath('results.0.title', 'Juan Pérez');

    $this->actingAs($user)
        ->getJson(route('search', ['current_team' => $team, 'q' => 'unidad 12']))
        ->assertJsonPath('results.0.type', 'vehicle')
        ->assertJsonPath('results.0.title', 'Unidad 12');
});

test('the palette never returns records of another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['name' => 'Acme Logistics', 'legal_name' => null]);
    Party::factory()->for(Team::factory()->create())->create([
        'name' => 'Someone Else',
        'legal_name' => 'Acme Logistics del Norte',
    ]);

    $response = $this->actingAs($user)->getJson(route('search', ['current_team' => $team, 'q' => 'acme']));

    $response->assertOk()->assertJsonCount(1, 'results');

    expect($response->json('results.0.title'))->toBe('Acme Logistics');
});

test('a term shorter than two characters returns nothing', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['name' => 'Acme Logistics']);

    $this->actingAs($user)
        ->getJson(route('search', ['current_team' => $team, 'q' => 'a']))
        ->assertOk()
        ->assertJsonCount(0, 'results');
});

test('guests cannot search', function () {
    $team = Team::factory()->create();

    $this->getJson(route('search', ['current_team' => $team, 'q' => 'acme']))->assertUnauthorized();
});

test('users who are not members of the team cannot search it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($user)
        ->getJson(route('search', ['current_team' => $team, 'q' => 'acme']))
        ->assertForbidden();
});
