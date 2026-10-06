<?php

use App\Enums\StopStatus;
use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;

test('a driver moves a stop through arrived and completed', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create(['status' => StopStatus::Pending]);

    $this->actingAs($user)
        ->patch(route('driver.trips.stops.status.update', [$team, $trip, $stop]), ['status' => StopStatus::Arrived->value])
        ->assertRedirect();

    $this->assertDatabaseHas('stops', ['id' => $stop->id, 'status' => StopStatus::Arrived->value]);

    $this->actingAs($user)
        ->patch(route('driver.trips.stops.status.update', [$team, $trip, $stop]), ['status' => StopStatus::Completed->value])
        ->assertRedirect();

    $this->assertDatabaseHas('stops', ['id' => $stop->id, 'status' => StopStatus::Completed->value]);
});

test('an invalid stop transition is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create(['status' => StopStatus::Completed]);

    $this->actingAs($user)
        ->from(route('driver.trips.show', [$team, $trip]))
        ->patch(route('driver.trips.stops.status.update', [$team, $trip, $stop]), ['status' => StopStatus::Pending->value])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('stops', ['id' => $stop->id, 'status' => StopStatus::Completed->value]);
});

test('a driver may only change stops on their assigned trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();
    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);
    $stop = Stop::factory()->for($team)->for($theirs)->create();

    $this->actingAs($driverUser)
        ->patch(route('driver.trips.stops.status.update', [$team, $theirs, $stop]), ['status' => StopStatus::Arrived->value])
        ->assertForbidden();
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
        ->patch(route('driver.trips.stops.status.update', [$team, $trip, $foreignStop]), ['status' => StopStatus::Arrived->value])
        ->assertNotFound();
});
