<?php

use App\Enums\TeamRole;
use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\Team;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('a document can be attached to a driver', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('drivers.documents.store', [$team, $driver]), [
            'type' => 'insurance',
            'number' => ' POL-123 ',
            'issued_at' => '2026-01-01',
            'expires_at' => '2027-01-01',
            'notes' => 'Covers the whole fleet',
        ])
        ->assertRedirect();

    $document = ComplianceDocument::query()->withoutGlobalScope('team')->sole();

    expect($document->team_id)->toBe($team->id)
        ->and($document->documentable_type)->toBe(Driver::class)
        ->and($document->documentable_id)->toBe($driver->id)
        ->and($document->number)->toBe('POL-123');
});

test('a document can be attached to a vehicle and a trailer', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $vehicle = Vehicle::factory()->for($team)->create();
    $trailer = Trailer::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('vehicles.documents.store', [$team, $vehicle]), ['type' => 'verification'])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('trailers.documents.store', [$team, $trailer]), ['type' => 'inspection'])
        ->assertRedirect();

    $this->assertDatabaseHas('compliance_documents', [
        'documentable_type' => Vehicle::class,
        'documentable_id' => $vehicle->id,
        'type' => 'verification',
    ]);

    $this->assertDatabaseHas('compliance_documents', [
        'documentable_type' => Trailer::class,
        'documentable_id' => $trailer->id,
        'type' => 'inspection',
    ]);
});

test('a document requires a valid type', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();

    $this->actingAs($user)
        ->from(route('drivers.show', [$team, $driver]))
        ->post(route('drivers.documents.store', [$team, $driver]), ['type' => 'not-a-type'])
        ->assertSessionHasErrors('type');

    $this->assertDatabaseCount('compliance_documents', 0);
});

test('a document cannot be attached through another team vehicle', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherVehicle = Vehicle::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->post(route('vehicles.documents.store', [$team, $otherVehicle]), ['type' => 'insurance'])
        ->assertNotFound();

    $this->assertDatabaseCount('compliance_documents', 0);
});

test('a document of another unit is not reachable through scoped bindings', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();
    $otherDriver = Driver::factory()->for($team)->create();
    $document = ComplianceDocument::factory()->for($team)->for($driver, 'documentable')->create();

    $this->actingAs($user)
        ->patch(route('drivers.documents.update', [$team, $otherDriver, $document]), ['type' => 'insurance'])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('drivers.documents.destroy', [$team, $otherDriver, $document]))
        ->assertNotFound();
});

test('documents can be updated and deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();
    $document = ComplianceDocument::factory()->for($team)->for($driver, 'documentable')->create(['type' => 'license']);

    $this->actingAs($user)
        ->patch(route('drivers.documents.update', [$team, $driver, $document]), [
            'type' => 'insurance',
            'number' => 'POL-999',
        ])
        ->assertRedirect();

    expect($document->fresh()->type->value)->toBe('insurance');

    $this->actingAs($user)
        ->delete(route('drivers.documents.destroy', [$team, $driver, $document]))
        ->assertRedirect();

    $this->assertSoftDeleted('compliance_documents', ['id' => $document->id]);
});

test('members without catalog permissions cannot change documents', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);
    $driver = Driver::factory()->for($team)->create();

    $this->actingAs($user)
        ->post(route('drivers.documents.store', [$team, $driver]), ['type' => 'insurance'])
        ->assertForbidden();

    $this->assertDatabaseCount('compliance_documents', 0);
});

test('an expired document is flagged in the detail and the roster', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();
    ComplianceDocument::factory()->for($team)->for($driver, 'documentable')->expired()->create();

    $this->actingAs($user)
        ->get(route('drivers.show', [$team, $driver]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('driver.documents.0.expired', true)
            ->where('driver.has_expired_documents', true));

    $this->actingAs($user)
        ->get(route('drivers.index', $team))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drivers.data.0.has_expired_documents', true)
            ->where('drivers.data.0.documents_count', 1));
});
