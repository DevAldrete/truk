<?php

use App\Enums\OrderStatus;
use App\Enums\PartyType;
use App\Enums\TeamRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('the order book lists the orders of the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Order::factory()->for($team)->create(['number' => 'ORD-00001']);
    Order::factory()->for(Team::factory()->create())->create(['number' => 'ORD-99999']);

    $this->actingAs($user)
        ->get(route('orders.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'ORD-00001'));
});

test('an order can be created with items in base units', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $customer = Party::factory()->for($team)->type(PartyType::Customer)->create([
        'name' => 'Acme Logistics',
        'rfc' => 'ACM010101AAA',
    ]);

    $response = $this->actingAs($user)->post(route('orders.store', $team), [
        'customer_party_id' => $customer->id,
        'status' => OrderStatus::Draft->value,
        'currency' => 'MXN',
        'requested_pickup_at' => '2026-11-01 09:00:00',
        'items' => [
            [
                'description' => 'Cajas de cerveza',
                'quantity' => 10,
                'unit' => 'piece',
                'weight_kg' => 12.5,
                'volume_m3' => 0.4,
                'hazmat' => false,
            ],
        ],
    ]);

    $order = Order::query()->withoutGlobalScope('team')->sole();

    $response->assertRedirect(route('orders.show', [$team, $order]));

    expect($order->number)->toBe('ORD-00001')
        ->and($order->customer_name)->toBe('Acme Logistics')
        ->and($order->customer_rfc)->toBe('ACM010101AAA')
        ->and($order->status)->toBe(OrderStatus::Draft);

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'team_id' => $team->id,
        'description' => 'Cajas de cerveza',
        'weight_grams' => 12500,
        'volume_cm3' => 400000,
        'hazmat' => false,
    ]);
});

test('an order requires at least one item', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user)
        ->from(route('orders.index', $team))
        ->post(route('orders.store', $team), [
            'status' => OrderStatus::Draft->value,
            'currency' => 'MXN',
            'items' => [],
        ])
        ->assertSessionHasErrors('items');

    $this->assertDatabaseCount('orders', 0);
});

test('a deleted middle order does not make the next number collide', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Order::factory()->for($team)->create(['number' => 'ORD-00001']);
    Order::factory()->for($team)->create(['number' => 'ORD-00002']);
    Order::factory()->for($team)->create(['number' => 'ORD-00003']);

    // A hard delete drops the row count but not the highest suffix; the next
    // number must still be one past the highest, never a live number.
    DB::table('orders')->where('number', 'ORD-00002')->delete();

    $this->actingAs($user)->post(route('orders.store', $team), [
        'status' => OrderStatus::Draft->value,
        'currency' => 'MXN',
        'items' => [['description' => 'Cajas', 'quantity' => 1, 'unit' => 'piece']],
    ])->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'team_id' => $team->id,
        'number' => 'ORD-00004',
    ]);
});

test('an order can only use a customer of the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherCustomer = Party::factory()->for(Team::factory()->create())->type(PartyType::Customer)->create();

    $this->actingAs($user)
        ->from(route('orders.index', $team))
        ->post(route('orders.store', $team), [
            'customer_party_id' => $otherCustomer->id,
            'status' => OrderStatus::Draft->value,
            'currency' => 'MXN',
            'items' => [
                ['description' => 'Cajas', 'quantity' => 1, 'unit' => 'piece'],
            ],
        ])
        ->assertSessionHasErrors('customer_party_id');

    $this->assertDatabaseCount('orders', 0);
});

test('updating an order replaces its items', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $order = Order::factory()->for($team)->create();
    OrderItem::factory()->for($team)->for($order)->create(['description' => 'Old line']);

    $this->actingAs($user)
        ->patch(route('orders.update', [$team, $order]), [
            'status' => OrderStatus::Confirmed->value,
            'currency' => 'MXN',
            'items' => [
                ['description' => 'New line', 'quantity' => 3, 'unit' => 'piece', 'weight_kg' => 2],
            ],
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
    $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'description' => 'New line']);
    $this->assertDatabaseMissing('order_items', ['order_id' => $order->id, 'description' => 'Old line']);
});

test('orders can be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $order = Order::factory()->for($team)->create();

    $this->actingAs($user)
        ->delete(route('orders.destroy', [$team, $order]))
        ->assertRedirect(route('orders.index', $team));

    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});

test('warehouse members cannot manage orders', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);

    $this->actingAs($user)->post(route('orders.store', $team), [
        'status' => OrderStatus::Draft->value,
        'currency' => 'MXN',
        'items' => [['description' => 'Cajas', 'quantity' => 1, 'unit' => 'piece']],
    ])->assertForbidden();

    $this->assertDatabaseCount('orders', 0);
});

test('orders of another team are not reachable', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherOrder = Order::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)->get(route('orders.show', [$team, $otherOrder]))->assertNotFound();
});
