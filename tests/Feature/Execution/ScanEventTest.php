<?php

use App\Enums\PackageStatus;
use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Build a trip with a stop and a packaged shipment attached to it.
 *
 * @return array{0: Trip, 1: Stop, 2: Shipment, 3: Package}
 */
function packageOnTrip(Team $team): array
{
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 1]);
    $team->stopShipments()->create(['stop_id' => $stop->id, 'shipment_id' => $shipment->id]);
    $package = Package::factory()->for($team)->for($shipment)->create();

    return [$trip, $stop, $shipment, $package];
}

test('scans move the package through the custody map', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment, $package] = packageOnTrip($team);

    foreach (['loaded', 'in_transit', 'delivered'] as $type) {
        $this->actingAs($user)->post(route('driver.trips.scans.store', [$team, $trip]), [
            'package_id' => $package->id,
            'stop_id' => $stop->id,
            'type' => $type,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
    }

    $this->assertDatabaseHas('scan_events', ['package_id' => $package->id, 'trip_id' => $trip->id, 'shipment_id' => $shipment->id]);
    $this->assertDatabaseHas('packages', ['id' => $package->id, 'status' => PackageStatus::Delivered->value]);
});

test('out-of-order scans resolve by occurred_at', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment, $package] = packageOnTrip($team);

    $post = function (string $type, string $occurredAt) use ($user, $team, $trip, $package): void {
        $this->actingAs($user)->post(route('driver.trips.scans.store', [$team, $trip]), [
            'package_id' => $package->id,
            'type' => $type,
            'occurred_at' => $occurredAt,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
    };

    // The delivered scan lands first but the loading scans happened earlier.
    $post('delivered', now()->toIso8601String());
    $post('loaded', now()->subHours(3)->toIso8601String());
    $post('in_transit', now()->subHours(1)->toIso8601String());

    $this->assertDatabaseHas('packages', ['id' => $package->id, 'status' => PackageStatus::Delivered->value]);
});

test('a delivered package cannot be moved backwards', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment, $package] = packageOnTrip($team);

    foreach (['loaded', 'in_transit', 'delivered', 'loaded'] as $type) {
        $this->actingAs($user)->post(route('driver.trips.scans.store', [$team, $trip]), [
            'package_id' => $package->id,
            'type' => $type,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
    }

    $this->assertDatabaseHas('packages', ['id' => $package->id, 'status' => PackageStatus::Delivered->value]);
});

test('a double scan submit with the same key returns the same event', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment, $package] = packageOnTrip($team);
    $key = (string) Str::uuid();

    $payload = [
        'package_id' => $package->id,
        'type' => 'loaded',
        'idempotency_key' => $key,
    ];

    $this->actingAs($user)->post(route('driver.trips.scans.store', [$team, $trip]), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('driver.trips.scans.store', [$team, $trip]), $payload)->assertRedirect();

    $this->assertDatabaseCount('scan_events', 1);
});

test('a package must be on the trip to be scanned', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment, $package] = packageOnTrip($team);

    $offShipment = Shipment::factory()->for($team)->create();
    $offTrip = Package::factory()->for($team)->for($offShipment)->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.scans.store', [$team, $trip]), [
            'package_id' => $offTrip->id,
            'type' => 'loaded',
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('package_id');

    $this->assertDatabaseCount('scan_events', 0);
});

test('a driver may only scan on their assigned trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();

    [, , , $package] = packageOnTrip($team);

    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);

    $this->actingAs($driverUser)->post(route('driver.trips.scans.store', [$team, $theirs]), [
        'package_id' => $package->id,
        'type' => 'loaded',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();
});

test('a package of another team cannot be scanned', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment] = packageOnTrip($team);

    $foreignTeam = Team::factory()->create();
    $foreignShipment = Shipment::factory()->for($foreignTeam)->create();
    $foreignPackage = Package::factory()->for($foreignTeam)->for($foreignShipment)->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.scans.store', [$team, $trip]), [
            'package_id' => $foreignPackage->id,
            'type' => 'loaded',
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('package_id');

    $this->assertDatabaseCount('scan_events', 0);
});
