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
use Inertia\Testing\AssertableInertia as Assert;

test('the dispatch board lists open trips and unassigned shipments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Planned]);
    Trip::factory()->for($team)->create(['status' => TripStatus::Completed]);

    $assigned = Shipment::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();
    StopShipment::factory()->for($team)->for($stop)->for($assigned)->create();

    $pool = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->get(route('dispatch', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dispatch/Index')
            ->has('trips', 1)
            ->has('pool', 1)
            ->where('pool.0.id', $pool->id));
});

test('a shipment can be assigned to and removed from a trip', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $trip = Trip::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $shipment->id])
        ->assertRedirect();

    $this->assertDatabaseHas('stop_shipments', ['shipment_id' => $shipment->id, 'team_id' => $team->id]);
    $this->assertDatabaseCount('stops', 1);

    $this->actingAs($user)
        ->delete(route('trips.shipments.destroy', [$team, $trip, $shipment]))
        ->assertRedirect();

    $this->assertDatabaseMissing('stop_shipments', ['shipment_id' => $shipment->id]);
});

test('assigning the same shipment twice does not duplicate it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $shipment->id]);
    $this->actingAs($user)->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $shipment->id]);

    $this->assertDatabaseCount('stop_shipments', 1);
});

test('a shipment of another team cannot be assigned', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $otherShipment = Shipment::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $otherShipment->id])
        ->assertSessionHasErrors('shipment_id');
});

test('a planned trip can be dispatched within capacity', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $vehicle = Vehicle::factory()->for($team)->create(['max_payload_grams' => 1000000, 'max_volume_cm3' => null]);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Planned, 'vehicle_id' => $vehicle->id]);

    $this->actingAs($user)
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertRedirect();

    expect($trip->fresh()->status)->toBe(TripStatus::Dispatched);
});

test('dispatching an over-capacity trip is blocked without the override permission', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $vehicle = Vehicle::factory()->for($team)->create(['max_payload_grams' => 1000, 'max_volume_cm3' => null]);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Planned, 'vehicle_id' => $vehicle->id]);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create(['weight_grams' => 5000]);
    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertSessionHasErrors('status');

    expect($trip->fresh()->status)->toBe(TripStatus::Planned);
});

test('only a planned trip can be dispatched', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create(['status' => TripStatus::Dispatched]);

    $this->actingAs($user)
        ->from(route('dispatch', $team))
        ->post(route('trips.dispatch', [$team, $trip]))
        ->assertSessionHasErrors('status');
});

test('warehouse members cannot assign shipments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $trip = Trip::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('trips.shipments.store', [$team, $trip]), ['shipment_id' => $shipment->id])
        ->assertForbidden();
});
