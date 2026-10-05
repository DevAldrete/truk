<?php

use App\Enums\TeamRole;
use App\Models\Location;
use App\Models\Party;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the directory lists the sites of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Location::factory()->for($team)->create(['name' => 'Bodega Norte']);
    Location::factory()->for(Team::factory()->create())->create(['name' => 'Other Tenant']);

    $response = $this->actingAs($user)->get(route('locations.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('locations/Index')
            ->has('locations.data', 1)
            ->where('locations.data.0.name', 'Bodega Norte'));
});

test('sites can be searched by city, street, and postal code', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Location::factory()->for($team)->create([
        'name' => 'Bodega Norte',
        'street' => 'Av. Constitución',
        'city' => 'Monterrey',
        'postal_code' => '64000',
    ]);
    Location::factory()->for($team)->create([
        'name' => 'Bodega Sur',
        'street' => 'Calle Hidalgo',
        'city' => 'Guadalajara',
        'postal_code' => '44100',
    ]);

    foreach (['monterrey', 'constitución', '64000'] as $term) {
        $this->actingAs($user)
            ->get(route('locations.index', ['current_team' => $team, 'search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('locations.data', 1)
                ->where('locations.data.0.name', 'Bodega Norte'));
    }
});

test('the detail panel includes the party the site belongs to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create(['name' => 'Acme Logistics']);
    $location = Location::factory()->for($team)->for($party)->create();

    $this->actingAs($user)
        ->get(route('locations.show', [$team, $location]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('locations/Index')
            ->where('location.id', $location->id)
            ->where('location.party_name', 'Acme Logistics'));
});

test('sites can be created', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $response = $this->actingAs($user)->post(route('locations.store', $team), [
        'name' => '  Bodega Norte  ',
        'street' => 'Av. Constitución',
        'exterior_number' => '1200',
        'neighborhood' => 'Centro',
        'city' => 'Monterrey',
        'state' => 'Nuevo León',
        'postal_code' => '64000',
        'references' => 'Portón azul, junto a la farmacia',
    ]);

    $location = Location::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('locations.show', [$team, $location]));

    expect($location->team_id)->toBe($team->id)
        ->and($location->name)->toBe('Bodega Norte')
        ->and($location->party_id)->toBeNull();
});

test('an address requires the street, city, state, and postal code', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->from(route('locations.index', $team))
        ->post(route('locations.store', $team), ['name' => 'Bodega Norte']);

    $response->assertSessionHasErrors(['street', 'city', 'state', 'postal_code']);

    $this->assertDatabaseCount('locations', 0);
});

test('a site can only be linked to a party of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $response = $this->actingAs($user)
        ->from(route('locations.index', $team))
        ->post(route('locations.store', $team), [
            'name' => 'Bodega Norte',
            'party_id' => $otherParty->id,
            'street' => 'Av. Constitución',
            'city' => 'Monterrey',
            'state' => 'Nuevo León',
            'postal_code' => '64000',
        ]);

    $response->assertSessionHasErrors('party_id');

    $this->assertDatabaseCount('locations', 0);
});

test('sites can be updated and deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $location = Location::factory()->for($team)->create(['name' => 'Bodega Norte']);

    $this->actingAs($user)
        ->patch(route('locations.update', [$team, $location]), [
            'name' => 'Bodega Norte 2',
            'street' => 'Av. Constitución',
            'city' => 'Monterrey',
            'state' => 'Nuevo León',
            'postal_code' => '64001',
        ])
        ->assertRedirect();

    expect($location->fresh()->name)->toBe('Bodega Norte 2');

    $this->actingAs($user)
        ->delete(route('locations.destroy', [$team, $location]))
        ->assertRedirect(route('locations.index', $team));

    $this->assertSoftDeleted('locations', ['id' => $location->id]);
});

test('members without catalog permissions cannot change sites', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $location = Location::factory()->for($team)->create();

    $this->actingAs($user)->post(route('locations.store', $team), [
        'name' => 'Bodega Norte',
        'street' => 'Av. Constitución',
        'city' => 'Monterrey',
        'state' => 'Nuevo León',
        'postal_code' => '64000',
    ])->assertForbidden();

    $this->actingAs($user)
        ->delete(route('locations.destroy', [$team, $location]))
        ->assertForbidden();

    $this->assertDatabaseCount('locations', 1);
});

test('sites of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherLocation = Location::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('locations.show', [$team, $otherLocation]))->assertNotFound();

    $this->actingAs($user)
        ->delete(route('locations.destroy', [$team, $otherLocation]))
        ->assertNotFound();
});
