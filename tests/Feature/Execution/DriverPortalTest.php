<?php

use App\Enums\ShipmentStatus;
use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('a driver only sees their own open trips', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();

    Trip::factory()->for($team)->create(['driver_id' => $driver->id, 'status' => TripStatus::Dispatched]);
    Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id, 'status' => TripStatus::Dispatched]);
    Trip::factory()->for($team)->create(['driver_id' => $driver->id, 'status' => TripStatus::Completed]);

    $this->actingAs($driverUser)
        ->get(route('driver.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/Trips')
            ->has('trips', 1)
            ->where('driver.id', $driver->id));
});

test('a driver without a linked profile sees no trips', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);

    Trip::factory()->for($team)->create(['status' => TripStatus::Dispatched]);

    $this->actingAs($driverUser)
        ->get(route('driver.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/Trips')
            ->has('trips', 0)
            ->where('driver', null));
});

test('a planner sees every open trip', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    Trip::factory()->for($team)->count(2)->create(['status' => TripStatus::Dispatched]);
    Trip::factory()->for($team)->create(['status' => TripStatus::Completed]);

    $this->actingAs($user)
        ->get(route('driver.index', $team))
        ->assertInertia(fn (Assert $page) => $page->has('trips', 2)->where('driver', null));
});

test('a warehouse member cannot use the driver portal', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);

    $this->actingAs($user)
        ->get(route('driver.index', $team))
        ->assertForbidden();
});

test('a planner can leave the portal for the team dashboard', function () {
    $team = Team::factory()->create();
    $planner = User::factory()->create();
    $team->members()->attach($planner, ['role' => TeamRole::Dispatcher->value]);

    $this->actingAs($planner)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});

test('a driver cannot open another driver trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();
    $trip = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);

    $this->actingAs($driverUser)
        ->get(route('driver.trips.show', [$team, $trip]))
        ->assertForbidden();
});

test('the trip screen exposes stops, shipments, packages and remaining quantity', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5, 'status' => ShipmentStatus::InTransit]);
    $team->stopShipments()->create(['stop_id' => $stop->id, 'shipment_id' => $shipment->id]);
    Package::factory()->for($team)->for($shipment)->create();

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'partially_delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 2, 'success' => true]],
    ])->assertRedirect();

    $this->actingAs($user)
        ->get(route('driver.trips.show', [$team, $trip]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/Trip')
            ->where('trip.id', $trip->id)
            ->has('trip.stops', 1)
            ->where('trip.stops.0.shipments.0.pieces', 5)
            ->where('trip.stops.0.shipments.0.delivered_quantity', 2)
            ->where('trip.stops.0.shipments.0.remaining_quantity', 3)
            ->has('trip.stops.0.shipments.0.packages', 1)
            ->has('trip.stops.0.attempts', 1)
            ->has('options.outcomes', 4));
});
