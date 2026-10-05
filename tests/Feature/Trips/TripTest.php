<?php

use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('the dispatch planner lists the trips of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Trip::factory()->for($team)->create(['number' => 'TRP-00001']);
    Trip::factory()->for(Team::factory()->create())->create(['number' => 'TRP-99999']);

    $this->actingAs($user)
        ->get(route('trips.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('trips/Index')
            ->has('trips.data', 1)
            ->where('trips.data.0.number', 'TRP-00001'));
});

test('a trip is created with a server assigned number', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $this->actingAs($user)
        ->post(route('trips.store', $team), [
            'status' => TripStatus::Planned->value,
            'planned_start_at' => '2026-11-01T08:00',
            'planned_end_at' => '2026-11-01T18:00',
            'timezone' => 'America/Monterrey',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('trips', ['team_id' => $team->id, 'number' => 'TRP-00001']);
});

test('trip status follows the allowed transitions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Planned]);

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertRedirect();

    expect($trip->fresh()->status)->toBe(TripStatus::Dispatched);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Completed->value])
        ->assertSessionHasErrors('status');

    expect($trip->fresh()->status)->toBe(TripStatus::Dispatched);
});

test('resources are assigned to a trip and recorded in history', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $driver = Driver::factory()->for($team)->create();
    $vehicle = Vehicle::factory()->for($team)->create();

    $this->actingAs($user)
        ->put(route('trips.resources.update', [$team, $trip]), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ])
        ->assertRedirect();

    expect($trip->fresh()->driver_id)->toBe($driver->id)
        ->and($trip->fresh()->vehicle_id)->toBe($vehicle->id);

    $this->assertDatabaseHas('trip_assignments', [
        'trip_id' => $trip->id,
        'driver_id' => $driver->id,
        'released_at' => null,
    ]);

    $this->actingAs($user)
        ->put(route('trips.resources.update', [$team, $trip]), ['driver_id' => null])
        ->assertRedirect();

    expect($trip->fresh()->driver_id)->toBeNull();
    $this->assertDatabaseMissing('trip_assignments', [
        'trip_id' => $trip->id,
        'driver_id' => $driver->id,
        'released_at' => null,
    ]);
});

test('a resource cannot be assigned to overlapping trips', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();

    $first = Trip::factory()->for($team)->create([
        'planned_start_at' => '2026-11-01T08:00:00',
        'planned_end_at' => '2026-11-01T18:00:00',
    ]);
    $second = Trip::factory()->for($team)->create([
        'planned_start_at' => '2026-11-01T10:00:00',
        'planned_end_at' => '2026-11-01T14:00:00',
    ]);

    $this->actingAs($user)
        ->put(route('trips.resources.update', [$team, $first]), ['driver_id' => $driver->id])
        ->assertRedirect();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $second]))
        ->put(route('trips.resources.update', [$team, $second]), ['driver_id' => $driver->id])
        ->assertSessionHasErrors('driver_id');

    expect($second->fresh()->driver_id)->toBeNull();
});

test('a resource can be assigned to non overlapping trips', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();

    $first = Trip::factory()->for($team)->create([
        'planned_start_at' => '2026-11-01T08:00:00',
        'planned_end_at' => '2026-11-01T18:00:00',
    ]);
    $second = Trip::factory()->for($team)->create([
        'planned_start_at' => '2026-11-02T08:00:00',
        'planned_end_at' => '2026-11-02T18:00:00',
    ]);

    $this->actingAs($user)
        ->put(route('trips.resources.update', [$team, $first]), ['driver_id' => $driver->id])
        ->assertRedirect();

    $this->actingAs($user)
        ->put(route('trips.resources.update', [$team, $second]), ['driver_id' => $driver->id])
        ->assertRedirect();

    expect($second->fresh()->driver_id)->toBe($driver->id);
});

test('a trip can only use resources of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $otherDriver = Driver::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->put(route('trips.resources.update', [$team, $trip]), ['driver_id' => $otherDriver->id])
        ->assertSessionHasErrors('driver_id');
});

test('trips can be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)
        ->delete(route('trips.destroy', [$team, $trip]))
        ->assertRedirect(route('trips.index', $team));

    $this->assertSoftDeleted('trips', ['id' => $trip->id]);
});

test('warehouse members cannot manage trips', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);

    $this->actingAs($user)
        ->post(route('trips.store', $team), ['status' => TripStatus::Planned->value])
        ->assertForbidden();

    $this->assertDatabaseCount('trips', 0);
});

test('trips of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherTrip = Trip::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('trips.show', [$team, $otherTrip]))->assertNotFound();
});
