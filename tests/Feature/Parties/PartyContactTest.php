<?php

use App\Enums\TeamRole;
use App\Models\Party;
use App\Models\PartyContact;
use App\Models\Team;
use App\Models\User;

test('contacts can be added to a party', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create();

    $response = $this->actingAs($user)->post(route('parties.contacts.store', [$team, $party]), [
        'name' => 'Ana Ruiz',
        'position' => 'Compras',
        'email' => 'ANA@ACME.MX',
        'phone' => '8181234567',
    ]);

    $response->assertRedirect();

    $contact = PartyContact::query()->sole();

    expect($contact->party_id)->toBe($party->id)
        ->and($contact->team_id)->toBe($team->id)
        ->and($contact->email)->toBe('ana@acme.mx');
});

test('a contact requires a name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create();

    $this->actingAs($user)
        ->from(route('parties.show', [$team, $party]))
        ->post(route('parties.contacts.store', [$team, $party]), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    $this->assertDatabaseCount('party_contacts', 0);
});

test('contacts can be updated and removed', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create();
    $contact = PartyContact::factory()->for($team)->for($party)->create(['name' => 'Ana Ruiz']);

    $this->actingAs($user)
        ->patch(route('parties.contacts.update', [$team, $party, $contact]), [
            'name' => 'Ana Ruiz de la Peña',
        ])
        ->assertRedirect();

    expect($contact->fresh()->name)->toBe('Ana Ruiz de la Peña');

    $this->actingAs($user)
        ->delete(route('parties.contacts.destroy', [$team, $party, $contact]))
        ->assertRedirect();

    $this->assertSoftDeleted('party_contacts', ['id' => $contact->id]);
});

test('a contact cannot be edited through another party', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $party = Party::factory()->for($team)->create();
    $otherParty = Party::factory()->for($team)->create();
    $contact = PartyContact::factory()->for($team)->for($otherParty)->create(['name' => 'Ana Ruiz']);

    $this->actingAs($user)
        ->patch(route('parties.contacts.update', [$team, $party, $contact]), ['name' => 'Hijacked'])
        ->assertNotFound();

    expect($contact->fresh()->name)->toBe('Ana Ruiz');
});

test('contacts of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->post(route('parties.contacts.store', [$team, $otherParty]), ['name' => 'Ana Ruiz'])
        ->assertNotFound();
});

test('members without catalog permissions cannot change contacts', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $party = Party::factory()->for($team)->create();
    $contact = PartyContact::factory()->for($team)->for($party)->create();

    $this->actingAs($user)
        ->post(route('parties.contacts.store', [$team, $party]), ['name' => 'Ana Ruiz'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('parties.contacts.destroy', [$team, $party, $contact]))
        ->assertForbidden();

    $this->assertDatabaseCount('party_contacts', 1);
});
