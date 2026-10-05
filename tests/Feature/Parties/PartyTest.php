<?php

use App\Enums\PartyType;
use App\Enums\TeamRole;
use App\Models\Location;
use App\Models\Party;
use App\Models\PartyContact;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the directory lists the parties of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['name' => 'Acme Logistics']);
    Party::factory()->for(Team::factory()->create())->create(['name' => 'Other Tenant']);

    $response = $this->actingAs($user)->get(route('parties.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parties/Index')
            ->has('parties.data', 1)
            ->where('parties.data.0.name', 'Acme Logistics')
            ->where('can.manage', true));
});

test('the directory can be filtered by type and searched by name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->type(PartyType::Customer)->create(['name' => 'Acme Logistics']);
    Party::factory()->for($team)->type(PartyType::Carrier)->create(['name' => 'Transportes del Norte']);

    $this->actingAs($user)
        ->get(route('parties.index', ['current_team' => $team, 'type' => 'carrier']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('parties.data', 1)
            ->where('parties.data.0.name', 'Transportes del Norte'));

    $this->actingAs($user)
        ->get(route('parties.index', ['current_team' => $team, 'search' => 'acme']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('parties.data', 1)
            ->where('parties.data.0.name', 'Acme Logistics'));
});

test('the detail panel includes the contacts and sites of the party', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $party = Party::factory()->for($team)->create();
    PartyContact::factory()->for($team)->for($party)->create(['name' => 'Ana Ruiz', 'position' => 'Compras']);
    Location::factory()->for($team)->for($party)->create([
        'name' => 'Bodega Norte',
        'street' => 'Av. Reforma',
        'city' => 'Monterrey',
        'state' => 'Nuevo León',
        'postal_code' => '64000',
    ]);

    $response = $this->actingAs($user)->get(route('parties.show', [$team, $party]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parties/Index')
            ->where('party.id', $party->id)
            ->where('party.contacts_count', 1)
            ->where('party.locations_count', 1)
            ->where('party.contacts.0.name', 'Ana Ruiz')
            ->where('party.locations.0.name', 'Bodega Norte'));
});

test('parties can be created in the team of the url', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $response = $this->actingAs($user)->post(route('parties.store', $team), [
        'type' => PartyType::Customer->value,
        'name' => '  Acme Logistics  ',
        'legal_name' => 'Acme Logistics SA de CV',
        'rfc' => 'abc123456xy9',
        'email' => 'CONTACTO@ACME.MX',
        'phone' => '8181234567',
    ]);

    $party = Party::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('parties.show', [$team, $party]));

    expect($party->team_id)->toBe($team->id)
        ->and($party->name)->toBe('Acme Logistics')
        ->and($party->rfc)->toBe('ABC123456XY9')
        ->and($party->email)->toBe('contacto@acme.mx');
});

test('a party requires a name and a valid type', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->from(route('parties.index', $team))
        ->post(route('parties.store', $team), ['type' => 'not-a-type', 'name' => '']);

    $response->assertSessionHasErrors(['type', 'name']);

    $this->assertDatabaseCount('parties', 0);
});

test('an invalid RFC is rejected with a translated message', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->from(route('parties.index', $team))
        ->post(route('parties.store', $team), [
            'type' => PartyType::Customer->value,
            'name' => 'Acme',
            'rfc' => 'ABC123',
        ]);

    $response->assertSessionHasErrors([
        'rfc' => 'El campo RFC no es un RFC válido.',
    ]);

    $this->assertDatabaseCount('parties', 0);
});

test('an RFC can only be used once per team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['rfc' => 'ABC123456XY9']);
    Party::factory()->for(Team::factory()->create())->create(['rfc' => 'ABC123456XY9']);

    $this->actingAs($user)
        ->from(route('parties.index', $team))
        ->post(route('parties.store', $team), [
            'type' => PartyType::Customer->value,
            'name' => 'Acme',
            'rfc' => 'ABC123456XY9',
        ])
        ->assertSessionHasErrors('rfc');

    $this->assertDatabaseCount('parties', 2);
});

test('the RFC of a deleted party can be reused', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Party::factory()->for($team)->create(['rfc' => 'ABC123456XY9'])->delete();

    $this->actingAs($user)
        ->post(route('parties.store', $team), [
            'type' => PartyType::Customer->value,
            'name' => 'Acme',
            'rfc' => 'ABC123456XY9',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('parties', [
        'team_id' => $team->id,
        'rfc' => 'ABC123456XY9',
        'deleted_at' => null,
    ]);
});

test('parties can be updated by dispatchers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $party = Party::factory()->for($team)->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->patch(route('parties.update', [$team, $party]), [
        'type' => PartyType::Carrier->value,
        'name' => 'New Name',
    ]);

    $response->assertRedirect();

    expect($party->fresh()->name)->toBe('New Name')
        ->and($party->fresh()->type)->toBe(PartyType::Carrier);
});

test('parties can be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create();

    $response = $this->actingAs($user)->delete(route('parties.destroy', [$team, $party]));

    $response->assertRedirect(route('parties.index', $team));

    $this->assertSoftDeleted('parties', ['id' => $party->id]);
});

test('members without catalog permissions cannot change parties', function (TeamRole $role) {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);
    $party = Party::factory()->for($team)->create();

    $this->actingAs($user)->post(route('parties.store', $team), [
        'type' => PartyType::Customer->value,
        'name' => 'Acme',
    ])->assertForbidden();

    $this->actingAs($user)->patch(route('parties.update', [$team, $party]), [
        'type' => PartyType::Customer->value,
        'name' => 'Acme',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('parties.destroy', [$team, $party]))->assertForbidden();

    expect($party->fresh()->name)->not->toBe('Acme');
})->with([
    'driver' => TeamRole::Driver,
    'viewer' => TeamRole::Member,
]);

test('parties of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('parties.show', [$team, $otherParty]))->assertNotFound();

    $this->actingAs($user)->patch(route('parties.update', [$team, $otherParty]), [
        'type' => PartyType::Customer->value,
        'name' => 'Hijacked',
    ])->assertNotFound();

    $this->actingAs($user)->delete(route('parties.destroy', [$team, $otherParty]))->assertNotFound();
});

test('users who are not members of the team cannot read its directory', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($user)->get(route('parties.index', $team))->assertForbidden();
});

test('guests are redirected to the login page', function () {
    $team = Team::factory()->create();

    $this->get(route('parties.index', $team))->assertRedirect(route('login'));
});
