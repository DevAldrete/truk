<?php

use App\Enums\LoadStatus;
use App\Enums\TeamRole;
use App\Models\Load;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the load planner lists the loads of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Load::factory()->for($team)->create(['number' => 'LOAD-00001']);
    Load::factory()->for(Team::factory()->create())->create(['number' => 'LOAD-99999']);

    $this->actingAs($user)
        ->get(route('loads.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('loads/Index')
            ->has('loads.data', 1)
            ->where('loads.data.0.number', 'LOAD-00001'));
});

test('a load detail lists the trips serving it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $load = Load::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->create(['load_id' => $load->id]);
    $trip = Trip::factory()->for($team)->create(['number' => 'TRP-00043']);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    $this->actingAs($user)
        ->get(route('loads.show', [$team, $load]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('load.trips', 1)
            ->where('load.trips.0.number', 'TRP-00043'));
});

test('a load is created with a server assigned number', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $this->actingAs($user)
        ->post(route('loads.store', $team), ['status' => LoadStatus::Draft->value])
        ->assertRedirect();

    $this->assertDatabaseHas('loads', ['team_id' => $team->id, 'number' => 'LOAD-00001']);
});

test('shipments can be grouped into and removed from a load', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $load = Load::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('loads.shipments.store', [$team, $load]), ['shipment_id' => $shipment->id])
        ->assertRedirect();

    expect($shipment->fresh()->load_id)->toBe($load->id);

    $this->actingAs($user)
        ->delete(route('loads.shipments.destroy', [$team, $load, $shipment]))
        ->assertRedirect();

    expect($shipment->fresh()->load_id)->toBeNull();
});

test('a load can only group a shipment of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $load = Load::factory()->for($team)->create();
    $otherShipment = Shipment::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->from(route('loads.show', [$team, $load]))
        ->post(route('loads.shipments.store', [$team, $load]), ['shipment_id' => $otherShipment->id])
        ->assertSessionHasErrors('shipment_id');

    expect($otherShipment->fresh()->load_id)->toBeNull();
});

test('a shipment can only be removed through the load it belongs to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $load = Load::factory()->for($team)->create();
    $otherLoad = Load::factory()->for($team)->create();
    $shipment = Shipment::factory()->for($team)->for($load, 'loadGroup')->create();

    $this->actingAs($user)
        ->delete(route('loads.shipments.destroy', [$team, $otherLoad, $shipment]))
        ->assertNotFound();
});

test('load status follows the allowed transitions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $load = Load::factory()->for($team)->create(['status' => LoadStatus::Draft]);

    $this->actingAs($user)
        ->patch(route('loads.update', [$team, $load]), ['status' => LoadStatus::Planned->value])
        ->assertRedirect();

    expect($load->fresh()->status)->toBe(LoadStatus::Planned);

    $this->actingAs($user)
        ->from(route('loads.show', [$team, $load]))
        ->patch(route('loads.update', [$team, $load]), ['status' => LoadStatus::Completed->value])
        ->assertSessionHasErrors('status');

    expect($load->fresh()->status)->toBe(LoadStatus::Planned);
});

test('loads can be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $load = Load::factory()->for($team)->create();

    $this->actingAs($user)
        ->delete(route('loads.destroy', [$team, $load]))
        ->assertRedirect(route('loads.index', $team));

    $this->assertSoftDeleted('loads', ['id' => $load->id]);
});

test('warehouse members cannot manage loads', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);

    $this->actingAs($user)
        ->post(route('loads.store', $team), ['status' => LoadStatus::Draft->value])
        ->assertForbidden();

    $this->assertDatabaseCount('loads', 0);
});

test('loads of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherLoad = Load::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('loads.show', [$team, $otherLoad]))->assertNotFound();
});
