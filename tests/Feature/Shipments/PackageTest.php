<?php

use App\Enums\TeamRole;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\User;

test('packages can be added to a shipment and numbering continues', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Dispatcher->value]);
    $shipment = Shipment::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('shipments.packages.store', [$team, $shipment]), [
            'count' => 3,
            'weight_kg' => 2.5,
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('packages', 3);

    $this->actingAs($user)
        ->post(route('shipments.packages.store', [$team, $shipment]), ['count' => 1])
        ->assertRedirect();

    expect(Package::query()->where('shipment_id', $shipment->id)->count())->toBe(4);
    expect(Package::query()->where('shipment_id', $shipment->id)->first()->weight_grams)->toBe(2500);
});

test('a package cannot be reached through another shipment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create();
    $otherShipment = Shipment::factory()->for($team)->create();
    $package = Package::factory()->for($team)->for($shipment)->create();

    $this->actingAs($user)
        ->delete(route('shipments.packages.destroy', [$team, $otherShipment, $package]))
        ->assertNotFound();

    $this->assertNotSoftDeleted('packages', ['id' => $package->id]);
});

test('a package can be removed from its shipment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $shipment = Shipment::factory()->for($team)->create();
    $package = Package::factory()->for($team)->for($shipment)->create();

    $this->actingAs($user)
        ->delete(route('shipments.packages.destroy', [$team, $shipment, $package]))
        ->assertRedirect();

    $this->assertSoftDeleted('packages', ['id' => $package->id]);
});
