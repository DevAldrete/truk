<?php

use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Enums\TeamRole;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;

test('stops are appended to the trip sequence', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('trips.stops.store', [$team, $trip]), ['type' => StopType::Delivery->value, 'status' => StopStatus::Pending->value])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('trips.stops.store', [$team, $trip]), ['type' => StopType::Pickup->value, 'status' => StopStatus::Pending->value])
        ->assertRedirect();

    $stops = Stop::query()->where('trip_id', $trip->id)->orderBy('sequence')->get();

    expect($stops)->toHaveCount(2)
        ->and($stops[0]->sequence)->toBe(1)
        ->and($stops[1]->sequence)->toBe(2);
});

test('a stop snapshots its location', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $location = Location::factory()->for($team)->create(['name' => 'Bodega Norte', 'street' => 'Av. Constitución']);

    $this->actingAs($user)->post(route('trips.stops.store', [$team, $trip]), [
        'type' => StopType::Pickup->value,
        'status' => StopStatus::Pending->value,
        'location_id' => $location->id,
    ])->assertRedirect();

    $stop = Stop::query()->where('trip_id', $trip->id)->sole();

    expect($stop->location_snapshot['street'])->toBe('Av. Constitución');
});

test('shipments can be attached to and detached from a stop', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('trips.stops.shipments.store', [$team, $trip, $stop]), ['shipment_id' => $shipment->id])
        ->assertRedirect();

    $this->assertDatabaseHas('stop_shipments', ['stop_id' => $stop->id, 'shipment_id' => $shipment->id, 'team_id' => $team->id]);

    $this->actingAs($user)
        ->delete(route('trips.stops.shipments.destroy', [$team, $trip, $stop, $shipment]))
        ->assertRedirect();

    $this->assertDatabaseMissing('stop_shipments', ['stop_id' => $stop->id, 'shipment_id' => $shipment->id]);
});

test('stops can be reordered', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $first = Stop::factory()->for($team)->for($trip)->create(['sequence' => 1]);
    $second = Stop::factory()->for($team)->for($trip)->create(['sequence' => 2]);

    $this->actingAs($user)
        ->put(route('trips.stops.reorder', [$team, $trip]), ['stop_ids' => [$second->id, $first->id]])
        ->assertRedirect();

    expect($second->fresh()->sequence)->toBe(1)
        ->and($first->fresh()->sequence)->toBe(2);
});

test('a stop cannot be reached through another trip', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $otherTrip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();

    $this->actingAs($user)
        ->delete(route('trips.stops.destroy', [$team, $otherTrip, $stop]))
        ->assertNotFound();
});

test('stop status follows the allowed transitions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create(['status' => StopStatus::Pending]);

    $this->actingAs($user)
        ->patch(route('trips.stops.update', [$team, $trip, $stop]), ['type' => StopType::Delivery->value, 'status' => StopStatus::Arrived->value])
        ->assertRedirect();

    expect($stop->fresh()->status)->toBe(StopStatus::Arrived);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->patch(route('trips.stops.update', [$team, $trip, $stop]), ['type' => StopType::Delivery->value, 'status' => StopStatus::Pending->value])
        ->assertSessionHasErrors('status');
});

test('warehouse members cannot manage stops', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('trips.stops.store', [$team, $trip]), ['type' => StopType::Delivery->value, 'status' => StopStatus::Pending->value])
        ->assertForbidden();

    $this->assertDatabaseCount('stops', 0);
});
