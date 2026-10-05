<?php

use App\Enums\ShipmentStatus;
use App\Enums\StopStatus;
use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Build a trip with one stop and attach the given shipments to it.
 *
 * @param  array<int, Shipment>  $shipments
 */
function tripWithStop(Team $team, array $shipments = []): array
{
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();

    foreach ($shipments as $shipment) {
        $team->stopShipments()->create(['stop_id' => $stop->id, 'shipment_id' => $shipment->id]);
    }

    return [$trip, $stop];
}

test('a full delivery derives the shipment as delivered', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 5, 'success' => true]],
    ])->assertRedirect();

    $this->assertDatabaseHas('delivery_attempts', ['stop_id' => $stop->id, 'team_id' => $team->id, 'outcome' => 'delivered']);
    $this->assertDatabaseHas('delivery_attempt_lines', ['shipment_id' => $shipment->id, 'quantity' => 5, 'success' => true]);
    $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => ShipmentStatus::Delivered->value]);
    $this->assertDatabaseHas('stops', ['id' => $stop->id, 'status' => StopStatus::Arrived->value]);
});

test('a short delivery derives the shipment as partially delivered', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'partially_delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 2, 'success' => true]],
    ])->assertRedirect();

    $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => ShipmentStatus::PartiallyDelivered->value]);
});

test('a failed attempt marks the shipment failed and stores the reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'failed',
        'failure_reason' => 'recipient_absent',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 0, 'success' => false]],
    ])->assertRedirect();

    $this->assertDatabaseHas('delivery_attempts', ['failure_reason' => 'recipient_absent']);
    $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => ShipmentStatus::Failed->value]);
});

test('a failure reason is required when the attempt failed', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
            'outcome' => 'failed',
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [['shipment_id' => $shipment->id, 'quantity' => 0, 'success' => false]],
        ])
        ->assertSessionHasErrors('failure_reason');

    $this->assertDatabaseCount('delivery_attempts', 0);
});

test('a delivered quantity above the plan is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
            'outcome' => 'delivered',
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [['shipment_id' => $shipment->id, 'quantity' => 6, 'success' => true]],
        ])
        ->assertSessionHasErrors('lines');

    $this->assertDatabaseCount('delivery_attempts', 0);
    $this->assertDatabaseCount('delivery_attempt_lines', 0);
});

test('quantities accumulate across attempts', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'partially_delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 3, 'success' => true]],
    ])->assertRedirect();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
            'outcome' => 'partially_delivered',
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [['shipment_id' => $shipment->id, 'quantity' => 3, 'success' => true]],
        ])
        ->assertSessionHasErrors('lines');

    $this->assertDatabaseCount('delivery_attempts', 1);
});

test('a double submit with the same key returns the same attempt', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);
    $key = (string) Str::uuid();

    $payload = [
        'outcome' => 'delivered',
        'idempotency_key' => $key,
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 5, 'success' => true]],
    ];

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), $payload)->assertRedirect();

    $this->assertDatabaseCount('delivery_attempts', 1);
    $this->assertDatabaseCount('delivery_attempt_lines', 1);
});

test('an attempt can only target a shipment served by the stop', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $onStop = Shipment::factory()->for($team)->create(['pieces' => 5]);
    $offStop = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$onStop]);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
            'outcome' => 'delivered',
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [['shipment_id' => $offStop->id, 'quantity' => 5, 'success' => true]],
        ])
        ->assertSessionHasErrors('lines');

    $this->assertDatabaseCount('delivery_attempts', 0);
});

test('a driver may only execute their assigned trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();

    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);

    $mine = Trip::factory()->for($team)->create(['driver_id' => $driver->id]);
    $mineStop = Stop::factory()->for($team)->for($mine)->create();
    $team->stopShipments()->create(['stop_id' => $mineStop->id, 'shipment_id' => $shipment->id]);

    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);
    $theirStop = Stop::factory()->for($team)->for($theirs)->create();
    $team->stopShipments()->create(['stop_id' => $theirStop->id, 'shipment_id' => $shipment->id]);

    $payload = [
        'outcome' => 'delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 5, 'success' => true]],
    ];

    $this->actingAs($driverUser)
        ->post(route('driver.trips.stops.attempts.store', [$team, $theirs, $theirStop]), $payload)
        ->assertForbidden();

    $this->actingAs($driverUser)
        ->post(route('driver.trips.stops.attempts.store', [$team, $mine, $mineStop]), $payload)
        ->assertRedirect();
});

test('a warehouse member cannot record an attempt', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $shipment = Shipment::factory()->for($team)->create(['pieces' => 5]);
    [$trip, $stop] = tripWithStop($team, [$shipment]);

    $this->actingAs($user)->post(route('driver.trips.stops.attempts.store', [$team, $trip, $stop]), [
        'outcome' => 'delivered',
        'idempotency_key' => (string) Str::uuid(),
        'lines' => [['shipment_id' => $shipment->id, 'quantity' => 5, 'success' => true]],
    ])->assertForbidden();

    $this->assertDatabaseCount('delivery_attempts', 0);
});

test('a stop of another team is not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();

    $otherTeam = Team::factory()->create();
    $foreignTrip = Trip::factory()->for($otherTeam)->create();
    $foreignStop = Stop::factory()->for($otherTeam)->for($foreignTrip)->create();

    $this->actingAs($user)
        ->post(route('driver.trips.stops.attempts.store', [$team, $trip, $foreignStop]), [
            'outcome' => 'delivered',
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [],
        ])
        ->assertNotFound();
});
