<?php

use App\Enums\ShipmentStatus;
use App\Enums\TeamRole;
use App\Models\Load;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the shipment board lists the shipments of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Shipment::factory()->for($team)->create(['number' => 'SHP-00001']);
    Shipment::factory()->for(Team::factory()->create())->create(['number' => 'SHP-99999']);

    $this->actingAs($user)
        ->get(route('shipments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shipments/Index')
            ->has('shipments.data', 1)
            ->where('shipments.data.0.number', 'SHP-00001'));
});

test('a shipment detail links to its load and serving trip', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $load = Load::factory()->for($team)->create(['number' => 'LOAD-00042']);
    $shipment = Shipment::factory()->for($team)->create(['load_id' => $load->id]);
    $trip = Trip::factory()->for($team)->create(['number' => 'TRP-00042']);
    $stop = Stop::factory()->for($team)->for($trip)->create();
    StopShipment::factory()->for($team)->for($stop)->for($shipment)->create();

    $this->actingAs($user)
        ->get(route('shipments.show', [$team, $shipment]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('shipment.load_number', 'LOAD-00042')
            ->where('shipment.trip_number', 'TRP-00042'));
});

test('changing the delivery site refreshes the address snapshot', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create();
    $delivery = Location::factory()->for($team)->create(['name' => 'CEDIS Sur', 'street' => 'Calle Hidalgo']);

    $this->actingAs($user)
        ->patch(route('shipments.update', [$team, $shipment]), [
            'delivery_location_id' => $delivery->id,
        ])
        ->assertRedirect();

    expect($shipment->fresh()->delivery_snapshot['street'])->toBe('Calle Hidalgo');
});

test('shipments can be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->delete(route('shipments.destroy', [$team, $shipment]))
        ->assertRedirect(route('shipments.index', $team));

    $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
});

test('warehouse members cannot manage shipments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->patch(route('shipments.update', [$team, $shipment]), ['status' => ShipmentStatus::Dispatched->value])
        ->assertForbidden();
});

test('shipments of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherShipment = Shipment::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('shipments.show', [$team, $otherShipment]))->assertNotFound();
});
