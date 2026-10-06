<?php

use App\Enums\ShipmentStatus;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

test('the demo seeder builds a coherent, connected dataset', function () {
    Storage::fake('evidence');

    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('teams', 5); // demo + one personal team per user
    $this->assertDatabaseCount('parties', 7);
    $this->assertDatabaseCount('locations', 12);
    $this->assertDatabaseCount('drivers', 3);
    $this->assertDatabaseCount('orders', 6);
    $this->assertDatabaseCount('shipments', 6);
    $this->assertDatabaseCount('loads', 2);
    $this->assertDatabaseCount('trips', 3);
    $this->assertDatabaseCount('incidents', 1);

    $teamId = Team::where('slug', 'transportes-del-norte')->value('id');

    // Execution derived the shipment statuses through the real use cases.
    $this->assertDatabaseHas('shipments', ['team_id' => $teamId, 'status' => ShipmentStatus::Delivered->value]);
    $this->assertDatabaseHas('shipments', ['team_id' => $teamId, 'status' => ShipmentStatus::PartiallyDelivered->value]);
    $this->assertDatabaseHas('shipments', ['team_id' => $teamId, 'status' => ShipmentStatus::Failed->value]);

    // Packages moved through custody and PODs were captured.
    $this->assertDatabaseHas('packages', ['team_id' => $teamId, 'status' => 'delivered']);
    $this->assertDatabaseCount('proofs_of_delivery', 2);
    $this->assertDatabaseCount('expenses', 6);

    expect(User::where('email', 'driver@truk.test')->exists())->toBeTrue();
});
