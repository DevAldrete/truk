<?php

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TeamRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\User;

test('an order converts into a shipment with snapshots, totals, and packages', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);

    $order = Order::factory()->for($team)->create(['customer_name' => 'Acme Logistics']);
    OrderItem::factory()->for($team)->for($order)->create([
        'quantity' => 10,
        'weight_grams' => 12500,
        'volume_cm3' => 400000,
    ]);

    $pickup = Location::factory()->for($team)->create(['name' => 'Bodega Norte', 'street' => 'Av. Constitución']);
    $delivery = Location::factory()->for($team)->create(['name' => 'CEDIS Sur']);

    $response = $this->actingAs($user)->post(route('orders.shipments.store', [$team, $order]), [
        'pickup_location_id' => $pickup->id,
        'delivery_location_id' => $delivery->id,
        'package_count' => 2,
    ]);

    $shipment = Shipment::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('shipments.show', [$team, $shipment]));

    expect($shipment->order_id)->toBe($order->id)
        ->and($shipment->status)->toBe(ShipmentStatus::Planned)
        ->and($shipment->customer_name)->toBe('Acme Logistics')
        ->and($shipment->weight_grams)->toBe(12500)
        ->and($shipment->volume_cm3)->toBe(400000)
        ->and($shipment->pieces)->toBe(10)
        ->and($shipment->pickup_snapshot['street'])->toBe('Av. Constitución')
        ->and($shipment->pickup_snapshot['name'])->toBe('Bodega Norte');

    $this->assertDatabaseHas('shipment_items', [
        'shipment_id' => $shipment->id,
        'team_id' => $team->id,
        'quantity' => 10,
    ]);

    $this->assertDatabaseCount('packages', 2);
});

test('a shipment cannot use locations of another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $order = Order::factory()->for($team)->create();
    $otherLocation = Location::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->from(route('orders.show', [$team, $order]))
        ->post(route('orders.shipments.store', [$team, $order]), [
            'pickup_location_id' => $otherLocation->id,
        ])
        ->assertSessionHasErrors('pickup_location_id');

    $this->assertDatabaseCount('shipments', 0);
});

test('warehouse members cannot convert orders into shipments', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $order = Order::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('orders.shipments.store', [$team, $order]), [])
        ->assertForbidden();

    $this->assertDatabaseCount('shipments', 0);
});

test('an order can only be converted into one shipment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $order = Order::factory()->for($team)->confirmed()->create();
    OrderItem::factory()->for($team)->for($order)->create(['quantity' => 1]);

    $this->actingAs($user)->post(route('orders.shipments.store', [$team, $order]))->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::InProgress);

    $this->actingAs($user)
        ->from(route('orders.show', [$team, $order]))
        ->post(route('orders.shipments.store', [$team, $order]))
        ->assertSessionHasErrors('order');

    $this->assertDatabaseCount('shipments', 1);
});

test('a cancelled order cannot be converted into a shipment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $order = Order::factory()->for($team)->create(['status' => OrderStatus::Cancelled->value]);
    OrderItem::factory()->for($team)->for($order)->create(['quantity' => 1]);

    $this->actingAs($user)
        ->from(route('orders.show', [$team, $order]))
        ->post(route('orders.shipments.store', [$team, $order]))
        ->assertSessionHasErrors('order');

    $this->assertDatabaseCount('shipments', 0);
});
