<?php

use App\Enums\PartyType;
use App\Enums\TeamRole;
use App\Models\Party;
use App\Models\Team;
use App\Models\Trailer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the roster lists the trailers of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Trailer::factory()->for($team)->create(['name' => 'Remolque 12']);
    Trailer::factory()->for(Team::factory()->create())->create(['name' => 'Other Tenant']);

    $response = $this->actingAs($user)->get(route('trailers.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('fleet/trailers/Index')
            ->has('trailers.data', 1)
            ->where('trailers.data.0.name', 'Remolque 12'));
});

test('trailers can be searched by name, plate, and configuration', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Trailer::factory()->for($team)->create([
        'name' => 'Remolque 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Refrigerado',
    ]);
    Trailer::factory()->for($team)->create([
        'name' => 'Remolque 99',
        'plate' => 'XYZ-98-76',
        'configuration' => 'Plataforma',
    ]);

    foreach (['remolque 12', 'abc-12', 'refrigerado'] as $term) {
        $this->actingAs($user)
            ->get(route('trailers.index', ['current_team' => $team, 'search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('trailers.data', 1)
                ->where('trailers.data.0.name', 'Remolque 12'));
    }
});

test('trailers can be created and the capacity is stored in base units', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $response = $this->actingAs($user)->post(route('trailers.store', $team), [
        'name' => '  Remolque 12  ',
        'plate' => ' abc-12-34 ',
        'configuration' => 'Refrigerado',
        'max_payload_kg' => '2000.5',
        'max_volume_m3' => '30.25',
    ]);

    $trailer = Trailer::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('trailers.show', [$team, $trailer]));

    expect($trailer->team_id)->toBe($team->id)
        ->and($trailer->name)->toBe('Remolque 12')
        ->and($trailer->plate)->toBe('ABC-12-34')
        ->and($trailer->max_payload_grams)->toBe(2000500)
        ->and($trailer->max_volume_cm3)->toBe(30250000);
});

test('a trailer requires a name, plate, configuration, and payload', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->from(route('trailers.index', $team))
        ->post(route('trailers.store', $team), []);

    $response->assertSessionHasErrors(['name', 'plate', 'configuration', 'max_payload_grams']);

    $this->assertDatabaseCount('trailers', 0);
});

test('a trailer can only be linked to a carrier party of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $response = $this->actingAs($user)
        ->from(route('trailers.index', $team))
        ->post(route('trailers.store', $team), [
            'name' => 'Remolque 12',
            'plate' => 'ABC-12-34',
            'configuration' => 'Refrigerado',
            'max_payload_kg' => '2000',
            'carrier_party_id' => $otherParty->id,
        ]);

    $response->assertSessionHasErrors('carrier_party_id');

    $this->assertDatabaseCount('trailers', 0);
});

test('a trailer can be assigned to a carrier party of the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $carrier = Party::factory()->for($team)->type(PartyType::Carrier)->create();

    $this->actingAs($user)->post(route('trailers.store', $team), [
        'name' => 'Remolque 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Refrigerado',
        'max_payload_kg' => '2000',
        'carrier_party_id' => $carrier->id,
    ])->assertRedirect();

    $trailer = Trailer::query()->withoutGlobalScope('team')->sole();

    expect($trailer->carrier_party_id)->toBe($carrier->id);
});

test('a live plate cannot be reused but is released after a soft delete', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trailer = Trailer::factory()->for($team)->create(['plate' => 'ABC-12-34']);

    $this->actingAs($user)
        ->from(route('trailers.index', $team))
        ->post(route('trailers.store', $team), [
            'name' => 'Remolque 99',
            'plate' => 'ABC-12-34',
            'configuration' => 'Plataforma',
            'max_payload_kg' => '1500',
        ])
        ->assertSessionHasErrors('plate');

    $trailer->delete();

    $this->actingAs($user)
        ->post(route('trailers.store', $team), [
            'name' => 'Remolque 99',
            'plate' => 'ABC-12-34',
            'configuration' => 'Plataforma',
            'max_payload_kg' => '1500',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('trailers', 2);
});

test('the roster exposes the capacity in kilograms and cubic metres', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Trailer::factory()->for($team)->create([
        'max_payload_grams' => 2000500,
        'max_volume_cm3' => 30250000,
    ]);

    $this->actingAs($user)
        ->get(route('trailers.index', $team))
        ->assertInertia(fn (Assert $page) => $page
            ->where('trailers.data.0.max_payload_kg', 2000.5)
            ->where('trailers.data.0.max_volume_m3', 30.25));
});

test('trailers can be updated and deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trailer = Trailer::factory()->for($team)->create(['name' => 'Remolque 12']);

    $this->actingAs($user)
        ->patch(route('trailers.update', [$team, $trailer]), [
            'name' => 'Remolque 12 Bis',
            'plate' => $trailer->plate,
            'configuration' => $trailer->configuration,
            'max_payload_kg' => '2000',
        ])
        ->assertRedirect();

    expect($trailer->fresh()->name)->toBe('Remolque 12 Bis');

    $this->actingAs($user)
        ->delete(route('trailers.destroy', [$team, $trailer]))
        ->assertRedirect(route('trailers.index', $team));

    $this->assertSoftDeleted('trailers', ['id' => $trailer->id]);
});

test('members without catalog permissions cannot change trailers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $trailer = Trailer::factory()->for($team)->create();

    $this->actingAs($user)->post(route('trailers.store', $team), [
        'name' => 'Remolque 12',
        'plate' => 'ABC-12-34',
        'configuration' => 'Refrigerado',
        'max_payload_kg' => '2000',
    ])->assertForbidden();

    $this->actingAs($user)
        ->delete(route('trailers.destroy', [$team, $trailer]))
        ->assertForbidden();

    $this->assertDatabaseCount('trailers', 1);
});

test('trailers of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherTrailer = Trailer::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('trailers.show', [$team, $otherTrailer]))->assertNotFound();

    $this->actingAs($user)
        ->delete(route('trailers.destroy', [$team, $otherTrailer]))
        ->assertNotFound();
});
