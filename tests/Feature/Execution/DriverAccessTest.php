<?php

use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a driver lands in the portal after logging in', function () {
    $team = Team::factory()->create();
    $driver = User::factory()->create();
    $team->members()->attach($driver, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driver->id]);
    $driver->update(['current_team_id' => $team->id]);

    $response = $this->post(route('login.store'), [
        'email' => $driver->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect("/{$team->slug}/driver");
});

test('the dashboard sends a driver to the portal', function () {
    $team = Team::factory()->create();
    $driver = User::factory()->create();
    $team->members()->attach($driver, ['role' => TeamRole::Driver->value]);

    $this->actingAs($driver)
        ->get(route('dashboard', $team))
        ->assertRedirect(route('driver.index', $team));
});

test('a driver cannot open the office lists', function () {
    $team = Team::factory()->create();
    $driver = User::factory()->create();
    $team->members()->attach($driver, ['role' => TeamRole::Driver->value]);

    $this->actingAs($driver)
        ->get(route('parties.index', $team))
        ->assertForbidden();

    $this->actingAs($driver)
        ->get(route('shipments.index', $team))
        ->assertForbidden();
});

test('a driver can still open the driver portal', function () {
    $team = Team::factory()->create();
    $driver = User::factory()->create();
    $team->members()->attach($driver, ['role' => TeamRole::Driver->value]);

    $this->actingAs($driver)
        ->get(route('driver.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('driver/Trips'));
});

test('a dispatcher keeps full access to the dashboard', function () {
    $team = Team::factory()->create();
    $dispatcher = User::factory()->create();
    $team->members()->attach($dispatcher, ['role' => TeamRole::Dispatcher->value]);

    $this->actingAs($dispatcher)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});
