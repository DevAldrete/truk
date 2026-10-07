<?php

use App\Enums\ShipmentStatus;
use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;

/**
 * Build a trip in the given status with one stop serving one shipment.
 *
 * @return array{0: Trip, 1: Shipment}
 */
function tripWithShipment(Team $team, TripStatus $status = TripStatus::Planned): array
{
    $trip = Trip::factory()->for($team)->create(['status' => $status]);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create([
        'status' => ShipmentStatus::Planned,
        'pieces' => 10,
    ]);

    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    return [$trip, $shipment];
}

function ownerOf(Team $team): User
{
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    return $user;
}

test('dispatching a trip marks its shipments dispatched', function () {
    $team = Team::factory()->create();
    $user = ownerOf($team);
    [$trip, $shipment] = tripWithShipment($team);

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value])
        ->assertRedirect();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Dispatched);
});

test('moving a trip to in transit marks its shipments in transit', function () {
    $team = Team::factory()->create();
    $user = ownerOf($team);
    [$trip, $shipment] = tripWithShipment($team);

    $this->actingAs($user)->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value]);
    $this->actingAs($user)->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::InTransit->value]);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::InTransit);
});

test('cancelling a dispatched trip fails its shipments', function () {
    $team = Team::factory()->create();
    $user = ownerOf($team);
    [$trip, $shipment] = tripWithShipment($team);

    $this->actingAs($user)->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Dispatched->value]);
    $this->actingAs($user)->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Cancelled->value]);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Failed);
});

test('cancelling a planned trip returns its shipments to planned', function () {
    $team = Team::factory()->create();
    $user = ownerOf($team);
    [$trip, $shipment] = tripWithShipment($team);

    $this->actingAs($user)
        ->patch(route('trips.update', [$team, $trip]), ['status' => TripStatus::Cancelled->value])
        ->assertRedirect();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Planned);
});

test('attaching a shipment to a dispatched trip marks it dispatched', function () {
    $team = Team::factory()->create();
    $user = ownerOf($team);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Dispatched]);
    $shipment = Shipment::factory()->for($team)->create(['status' => ShipmentStatus::Planned]);

    $this->actingAs($user)
        ->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $shipment->id])
        ->assertRedirect();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Dispatched);
});
