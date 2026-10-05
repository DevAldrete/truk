<?php

use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

/**
 * Build a planned trip whose single stop serves a shipment of the given weight.
 */
function plannedTripWithLoad(Team $team, int $weightGrams, int $vehicleLimitGrams): Trip
{
    $vehicle = Vehicle::factory()->for($team)->create([
        'max_payload_grams' => $vehicleLimitGrams,
        'max_volume_cm3' => null,
    ]);
    $trip = Trip::factory()->for($team)->create([
        'status' => TripStatus::Planned,
        'vehicle_id' => $vehicle->id,
    ]);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create(['weight_grams' => $weightGrams]);

    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    return $trip;
}

test('dispatching an over-capacity trip is blocked without the override permission', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = plannedTripWithLoad($team, weightGrams: 5000, vehicleLimitGrams: 1000);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertSessionHasErrors('status');

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

test('an admin can override an over-capacity dispatch with a reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $trip = plannedTripWithLoad($team, weightGrams: 5000, vehicleLimitGrams: 1000);

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), [
            'status' => TripStatus::Dispatched->value,
            'capacity_override_reason' => 'Urgent customer commitment',
        ])
        ->assertRedirect();

    $trip->refresh();

    expect($trip->status)->toBe(TripStatus::Dispatched)
        ->and($trip->capacity_override_reason)->toBe('Urgent customer commitment')
        ->and($trip->capacity_overridden_by)->toBe($user->id)
        ->and($trip->capacity_overridden_at)->not->toBeNull();
});

test('an override without a reason is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $trip = plannedTripWithLoad($team, weightGrams: 5000, vehicleLimitGrams: 1000);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertSessionHasErrors('capacity_override_reason');

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

test('a trip within capacity dispatches without an override', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = plannedTripWithLoad($team, weightGrams: 500, vehicleLimitGrams: 1000);

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertRedirect();

    expect($trip->fresh()->status)->toBe(TripStatus::Dispatched);
});

test('a trip without an assigned unit has no capacity limit', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Planned]);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create(['weight_grams' => 999999]);
    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertRedirect();

    expect($trip->fresh()->status)->toBe(TripStatus::Dispatched);
});
