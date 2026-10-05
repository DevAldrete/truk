<?php

use App\Enums\IncidentStatus;
use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Incident;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;

test('a driver reports an incident against the trip', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();
    $trip = Trip::factory()->for($team)->create(['driver_id' => $driver->id]);
    $stop = Stop::factory()->for($team)->for($trip)->create();

    $this->actingAs($user)->post(route('driver.trips.incidents.store', [$team, $trip]), [
        'type' => 'breakdown',
        'severity' => 'high',
        'description' => 'Tire blew out on the highway.',
        'stop_id' => $stop->id,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $this->assertDatabaseHas('incidents', [
        'team_id' => $team->id,
        'trip_id' => $trip->id,
        'stop_id' => $stop->id,
        'driver_id' => $driver->id,
        'type' => 'breakdown',
        'severity' => 'high',
        'status' => IncidentStatus::Open->value,
        'reported_by' => $user->id,
    ]);
});

test('an incident report is idempotent', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $key = (string) Str::uuid();

    $payload = [
        'type' => 'delay',
        'severity' => 'low',
        'description' => 'Traffic jam.',
        'idempotency_key' => $key,
    ];

    $this->actingAs($user)->post(route('driver.trips.incidents.store', [$team, $trip]), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('driver.trips.incidents.store', [$team, $trip]), $payload)->assertRedirect();

    $this->assertDatabaseCount('incidents', 1);
});

test('an incident requires a type and a description', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.incidents.store', [$team, $trip]), ['idempotency_key' => (string) Str::uuid()])
        ->assertSessionHasErrors(['type', 'description']);

    $this->assertDatabaseCount('incidents', 0);
});

test('a stop of another trip cannot be referenced', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $otherTrip = Trip::factory()->for($team)->create();
    $otherStop = Stop::factory()->for($team)->for($otherTrip)->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.incidents.store', [$team, $trip]), [
            'type' => 'delay',
            'severity' => 'low',
            'description' => 'Late.',
            'stop_id' => $otherStop->id,
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('stop_id');

    $this->assertDatabaseCount('incidents', 0);
});

test('a driver may only report against their assigned trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();
    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);

    $this->actingAs($driverUser)->post(route('driver.trips.incidents.store', [$team, $theirs]), [
        'type' => 'delay',
        'severity' => 'low',
        'description' => 'Late.',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();
});

test('a planner resolves an incident with a resolution', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $incident = Incident::factory()->for($team)->create(['status' => IncidentStatus::Open]);

    $this->actingAs($user)
        ->patch(route('incidents.update', [$team, $incident]), [
            'status' => IncidentStatus::Resolved->value,
            'resolution' => 'Replacement unit dispatched.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'status' => IncidentStatus::Resolved->value,
        'resolution' => 'Replacement unit dispatched.',
        'resolved_by' => $user->id,
    ]);
});

test('an invalid incident transition is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $incident = Incident::factory()->for($team)->create(['status' => IncidentStatus::Resolved]);

    $this->actingAs($user)
        ->from(route('trips.index', $team))
        ->patch(route('incidents.update', [$team, $incident]), ['status' => IncidentStatus::Open->value])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('incidents', ['id' => $incident->id, 'status' => IncidentStatus::Resolved->value]);
});

test('a resolution is required to close an incident', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $incident = Incident::factory()->for($team)->create(['status' => IncidentStatus::Open]);

    $this->actingAs($user)
        ->from(route('trips.index', $team))
        ->patch(route('incidents.update', [$team, $incident]), ['status' => IncidentStatus::Resolved->value])
        ->assertSessionHasErrors('resolution');
});

test('a warehouse member cannot resolve an incident', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $incident = Incident::factory()->for($team)->create();

    $this->actingAs($user)
        ->patch(route('incidents.update', [$team, $incident]), [
            'status' => IncidentStatus::Resolved->value,
            'resolution' => 'Done.',
        ])
        ->assertForbidden();
});

test('an incident of another team is not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $foreignIncident = Incident::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->patch(route('incidents.update', [$team, $foreignIncident]), [
            'status' => IncidentStatus::Investigating->value,
        ])
        ->assertNotFound();
});
