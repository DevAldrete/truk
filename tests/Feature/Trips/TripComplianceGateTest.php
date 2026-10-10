<?php

use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

/**
 * Build a planned trip with an expired driver licence.
 */
function nonCompliantTrip(Team $team): Trip
{
    $driver = Driver::factory()->for($team)->create(['license_expires_at' => now()->subDay()]);

    return Trip::factory()->for($team)->create([
        'status' => TripStatus::Planned,
        'driver_id' => $driver->id,
        'planned_start_at' => now(),
    ]);
}

test('dispatch is blocked when the driver licence is expired', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = nonCompliantTrip($team);

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => TripStatus::Planned->value]);
});

test('dispatch is blocked when a vehicle document is expired', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $vehicle = Vehicle::factory()->for($team)->create();

    ComplianceDocument::factory()
        ->for($team)
        ->for($vehicle, 'documentable')
        ->expired()
        ->create();

    $trip = Trip::factory()->for($team)->create([
        'status' => TripStatus::Planned,
        'vehicle_id' => $vehicle->id,
        'planned_start_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => TripStatus::Planned->value]);
});

test('an authorized user can override the compliance gate with a reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $trip = nonCompliantTrip($team);

    $this->actingAs($user)
        ->post(route('trips.dispatch', [$team, $trip]), [
            'compliance_override_reason' => 'Replacement licence in transit.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatus::Dispatched->value,
        'compliance_override_reason' => 'Replacement licence in transit.',
        'compliance_overridden_by' => $user->id,
    ]);

    expect(Trip::query()->withoutGlobalScope('team')->find($trip->id)->compliance_overridden_at)->not->toBeNull();
});

test('overriding the compliance gate requires a reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $trip = nonCompliantTrip($team);

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertSessionHasErrors('compliance_override_reason');

    $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => TripStatus::Planned->value]);
});

test('dispatching through the planner enforces the compliance gate', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = nonCompliantTrip($team);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->patch(route('trips.update', [$team, $trip]), [
            'status' => TripStatus::Dispatched->value,
        ])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => TripStatus::Planned->value]);
});
