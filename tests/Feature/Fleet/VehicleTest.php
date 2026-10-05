<?php

use App\Enums\PartyType;
use App\Enums\TeamRole;
use App\Models\Party;
use App\Models\Team;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('the roster lists the vehicles of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Vehicle::factory()->for($team)->create(['name' => 'Unidad 12']);
    Vehicle::factory()->for(Team::factory()->create())->create(['name' => 'Other Tenant']);

    $response = $this->actingAs($user)->get(route('vehicles.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('fleet/vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.name', 'Unidad 12'));
});

test('vehicles can be searched by name, plate, and configuration', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Vehicle::factory()->for($team)->create([
        'name' => 'Unidad 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Torton',
    ]);
    Vehicle::factory()->for($team)->create([
        'name' => 'Unidad 99',
        'plate' => 'XYZ-98-76',
        'configuration' => 'Van',
    ]);

    foreach (['unidad 12', 'abc-12', 'torton'] as $term) {
        $this->actingAs($user)
            ->get(route('vehicles.index', ['current_team' => $team, 'search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.name', 'Unidad 12'));
    }
});

test('vehicles can be created and the capacity is stored in base units', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $response = $this->actingAs($user)->post(route('vehicles.store', $team), [
        'name' => '  Unidad 12  ',
        'plate' => ' abc-12-34 ',
        'configuration' => 'Torton',
        'max_payload_kg' => '1500.5',
        'max_volume_m3' => '12.5',
    ]);

    $vehicle = Vehicle::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('vehicles.show', [$team, $vehicle]));

    expect($vehicle->team_id)->toBe($team->id)
        ->and($vehicle->name)->toBe('Unidad 12')
        ->and($vehicle->plate)->toBe('ABC-12-34')
        ->and($vehicle->max_payload_grams)->toBe(1500500)
        ->and($vehicle->max_volume_cm3)->toBe(12500000);
});

test('a vehicle requires a name, plate, configuration, and payload', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->from(route('vehicles.index', $team))
        ->post(route('vehicles.store', $team), []);

    $response->assertSessionHasErrors(['name', 'plate', 'configuration', 'max_payload_grams']);

    $this->assertDatabaseCount('vehicles', 0);
});

test('a vehicle can only be linked to a carrier party of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $response = $this->actingAs($user)
        ->from(route('vehicles.index', $team))
        ->post(route('vehicles.store', $team), [
            'name' => 'Unidad 12',
            'plate' => 'ABC-12-34',
            'configuration' => 'Torton',
            'max_payload_kg' => '1500',
            'carrier_party_id' => $otherParty->id,
        ]);

    $response->assertSessionHasErrors('carrier_party_id');

    $this->assertDatabaseCount('vehicles', 0);
});

test('a vehicle can be assigned to a carrier party of the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $carrier = Party::factory()->for($team)->type(PartyType::Carrier)->create();

    $this->actingAs($user)->post(route('vehicles.store', $team), [
        'name' => 'Unidad 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Torton',
        'max_payload_kg' => '1500',
        'carrier_party_id' => $carrier->id,
    ])->assertRedirect();

    $vehicle = Vehicle::query()->withoutGlobalScope('team')->sole();

    expect($vehicle->carrier_party_id)->toBe($carrier->id);
});

test('a live plate cannot be reused but is released after a soft delete', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $vehicle = Vehicle::factory()->for($team)->create(['plate' => 'ABC-12-34']);

    $this->actingAs($user)
        ->from(route('vehicles.index', $team))
        ->post(route('vehicles.store', $team), [
            'name' => 'Unidad 99',
            'plate' => 'ABC-12-34',
            'configuration' => 'Van',
            'max_payload_kg' => '900',
        ])
        ->assertSessionHasErrors('plate');

    $vehicle->delete();

    $this->actingAs($user)
        ->post(route('vehicles.store', $team), [
            'name' => 'Unidad 99',
            'plate' => 'ABC-12-34',
            'configuration' => 'Van',
            'max_payload_kg' => '900',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('vehicles', 2);
});

test('the roster exposes the capacity in kilograms and cubic metres', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Vehicle::factory()->for($team)->create([
        'max_payload_grams' => 1500500,
        'max_volume_cm3' => 12500000,
    ]);

    $this->actingAs($user)
        ->get(route('vehicles.index', $team))
        ->assertInertia(fn (Assert $page) => $page
            ->where('vehicles.data.0.max_payload_kg', 1500.5)
            ->where('vehicles.data.0.max_volume_m3', 12.5));
});

test('vehicles can be updated and deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $vehicle = Vehicle::factory()->for($team)->create(['name' => 'Unidad 12']);

    $this->actingAs($user)
        ->patch(route('vehicles.update', [$team, $vehicle]), [
            'name' => 'Unidad 12 Bis',
            'plate' => $vehicle->plate,
            'configuration' => $vehicle->configuration,
            'max_payload_kg' => '1500',
        ])
        ->assertRedirect();

    expect($vehicle->fresh()->name)->toBe('Unidad 12 Bis');

    $this->actingAs($user)
        ->delete(route('vehicles.destroy', [$team, $vehicle]))
        ->assertRedirect(route('vehicles.index', $team));

    $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
});

test('members without catalog permissions cannot change vehicles', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $vehicle = Vehicle::factory()->for($team)->create();

    $this->actingAs($user)->post(route('vehicles.store', $team), [
        'name' => 'Unidad 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Torton',
        'max_payload_kg' => '1500',
    ])->assertForbidden();

    $this->actingAs($user)
        ->delete(route('vehicles.destroy', [$team, $vehicle]))
        ->assertForbidden();

    $this->assertDatabaseCount('vehicles', 1);
});

test('vehicles of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherVehicle = Vehicle::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('vehicles.show', [$team, $otherVehicle]))->assertNotFound();

    $this->actingAs($user)
        ->delete(route('vehicles.destroy', [$team, $otherVehicle]))
        ->assertNotFound();
});
