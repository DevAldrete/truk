<?php

use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Party;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the roster lists the drivers of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Driver::factory()->for($team)->create(['name' => 'Juan Pérez']);
    Driver::factory()->for(Team::factory()->create())->create(['name' => 'Other Tenant']);

    $response = $this->actingAs($user)->get(route('drivers.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('fleet/drivers/Index')
            ->has('drivers.data', 1)
            ->where('drivers.data.0.name', 'Juan Pérez'));
});

test('drivers can be searched by name, phone, and licence', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Driver::factory()->for($team)->create([
        'name' => 'Juan Pérez',
        'phone' => '8112345678',
        'license_number' => 'ABC123456',
    ]);
    Driver::factory()->for($team)->create([
        'name' => 'María López',
        'phone' => '8198765432',
        'license_number' => 'XYZ987654',
    ]);

    foreach (['juan', '8112345678', 'abc123'] as $term) {
        $this->actingAs($user)
            ->get(route('drivers.index', ['current_team' => $team, 'search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('drivers.data', 1)
                ->where('drivers.data.0.name', 'Juan Pérez'));
    }
});

test('drivers can be created and the licence is normalised', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $response = $this->actingAs($user)->post(route('drivers.store', $team), [
        'name' => '  Juan Pérez  ',
        'phone' => ' 8112345678 ',
        'license_number' => ' abc123456 ',
        'license_expires_at' => '2030-01-15',
    ]);

    $driver = Driver::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('drivers.show', [$team, $driver]));

    expect($driver->team_id)->toBe($team->id)
        ->and($driver->name)->toBe('Juan Pérez')
        ->and($driver->phone)->toBe('8112345678')
        ->and($driver->license_number)->toBe('ABC123456')
        ->and($driver->carrier_party_id)->toBeNull();
});

test('a driver requires a name and a phone', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)
        ->from(route('drivers.index', $team))
        ->post(route('drivers.store', $team), []);

    $response->assertSessionHasErrors(['name', 'phone']);

    $this->assertDatabaseCount('drivers', 0);
});

test('a driver can only be linked to a carrier party of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherParty = Party::factory()->for(Team::factory()->create())->create();

    $response = $this->actingAs($user)
        ->from(route('drivers.index', $team))
        ->post(route('drivers.store', $team), [
            'name' => 'Juan Pérez',
            'phone' => '8112345678',
            'carrier_party_id' => $otherParty->id,
        ]);

    $response->assertSessionHasErrors('carrier_party_id');

    $this->assertDatabaseCount('drivers', 0);
});

test('a driver of the own fleet can be assigned to a carrier party of the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $carrier = Party::factory()->for($team)->type(PartyType::Carrier)->create();

    $this->actingAs($user)->post(route('drivers.store', $team), [
        'name' => 'Juan Pérez',
        'phone' => '8112345678',
        'carrier_party_id' => $carrier->id,
    ])->assertRedirect();

    $driver = Driver::query()->withoutGlobalScope('team')->sole();

    expect($driver->carrier_party_id)->toBe($carrier->id);
});

test('a live licence cannot be reused but is released after a soft delete', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create(['license_number' => 'ABC123456']);

    $this->actingAs($user)
        ->from(route('drivers.index', $team))
        ->post(route('drivers.store', $team), [
            'name' => 'María López',
            'phone' => '8198765432',
            'license_number' => 'ABC123456',
        ])
        ->assertSessionHasErrors('license_number');

    $driver->delete();

    $this->actingAs($user)
        ->post(route('drivers.store', $team), [
            'name' => 'María López',
            'phone' => '8198765432',
            'license_number' => 'ABC123456',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('drivers', 2);
});

test('the roster marks an expired licence so the UI can warn', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Driver::factory()->for($team)->create([
        'name' => 'Juan Pérez',
        'license_expires_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('drivers.index', $team))
        ->assertInertia(fn (Assert $page) => $page->where('drivers.data.0.license_expired', true));
});

test('drivers can be updated and deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create(['name' => 'Juan Pérez']);

    $this->actingAs($user)
        ->patch(route('drivers.update', [$team, $driver]), [
            'name' => 'Juan Pérez Gómez',
            'phone' => '8112345678',
        ])
        ->assertRedirect();

    expect($driver->fresh()->name)->toBe('Juan Pérez Gómez');

    $this->actingAs($user)
        ->delete(route('drivers.destroy', [$team, $driver]))
        ->assertRedirect(route('drivers.index', $team));

    $this->assertSoftDeleted('drivers', ['id' => $driver->id]);
});

test('members without catalog permissions cannot change drivers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create();

    $this->actingAs($user)->post(route('drivers.store', $team), [
        'name' => 'Juan Pérez',
        'phone' => '8112345678',
    ])->assertForbidden();

    $this->actingAs($user)
        ->delete(route('drivers.destroy', [$team, $driver]))
        ->assertForbidden();

    $this->assertDatabaseCount('drivers', 1);
});

test('drivers of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherDriver = Driver::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('drivers.show', [$team, $otherDriver]))->assertNotFound();

    $this->actingAs($user)
        ->delete(route('drivers.destroy', [$team, $otherDriver]))
        ->assertNotFound();
});

test('a driver can be linked to the login of a team member', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);

    $response = $this->actingAs($user)->post(route('drivers.store', $team), [
        'name' => 'Juan Pérez',
        'phone' => '8112345678',
        'user_id' => $driverUser->id,
    ]);

    $driver = Driver::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('drivers.show', [$team, $driver]));

    expect($driver->user_id)->toBe($driverUser->id)
        ->and($driverUser->driverProfileFor($team)?->id)->toBe($driver->id);
});

test('a driver can only be linked to a login of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $outsider = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('drivers.index', $team))
        ->post(route('drivers.store', $team), [
            'name' => 'Juan Pérez',
            'phone' => '8112345678',
            'user_id' => $outsider->id,
        ]);

    $response->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('drivers', 0);
});

test('a login cannot back two live drivers but is released after a soft delete', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);

    $this->actingAs($user)
        ->from(route('drivers.index', $team))
        ->post(route('drivers.store', $team), [
            'name' => 'María López',
            'phone' => '8198765432',
            'user_id' => $driverUser->id,
        ])
        ->assertSessionHasErrors('user_id');

    $driver->delete();

    $this->actingAs($user)
        ->post(route('drivers.store', $team), [
            'name' => 'María López',
            'phone' => '8198765432',
            'user_id' => $driverUser->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('drivers', 2);
});

test('the driver role may execute operations but not manage the catalog', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ExecuteOperations))->toBeTrue()
        ->and($user->hasTeamPermission($team, TeamPermission::ManageCatalog))->toBeFalse();
});
