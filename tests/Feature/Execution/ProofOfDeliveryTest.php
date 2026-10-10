<?php

use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\ProofOfDelivery;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

const POD_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

/**
 * Build a trip with a stop and a shipment attached.
 *
 * @return array{0: Trip, 1: Stop, 2: Shipment}
 */
function podSetup(Team $team): array
{
    $trip = Trip::factory()->for($team)->create();
    $stop = Stop::factory()->for($team)->for($trip)->create();
    $shipment = Shipment::factory()->for($team)->create();
    $team->stopShipments()->create(['stop_id' => $stop->id, 'shipment_id' => $shipment->id]);

    return [$trip, $stop, $shipment];
}

test('a proof of delivery stores its signature and photos privately', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop, $shipment] = podSetup($team);

    $this->actingAs($user)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
        'shipment_id' => $shipment->id,
        'recipient_name' => 'Ana López',
        'signature' => POD_SIGNATURE,
        'photos' => [UploadedFile::fake()->image('box.jpg')],
        'consent' => true,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $this->assertDatabaseCount('proofs_of_delivery', 1);

    $pod = ProofOfDelivery::query()->withoutGlobalScope('team')->sole();

    expect($pod->recipient_name)->toBe('Ana López')
        ->and($pod->consent)->toBeTrue()
        ->and($pod->shipment_id)->toBe($shipment->id)
        ->and($pod->signature_path)->not->toBeNull();

    Storage::disk('evidence')->assertExists($pod->signature_path);
    Storage::disk('evidence')->assertExists($pod->photos[0]);
});

test('a proof of delivery requires at least one piece of evidence', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
            'recipient_name' => 'Ana López',
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('signature');

    $this->assertDatabaseCount('proofs_of_delivery', 0);
});

test('a double pod submit with the same key returns the same record', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);
    $key = (string) Str::uuid();

    $payload = [
        'recipient_name' => 'Ana López',
        'signature' => POD_SIGNATURE,
        'idempotency_key' => $key,
    ];

    $this->actingAs($user)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), $payload)->assertRedirect();

    $this->assertDatabaseCount('proofs_of_delivery', 1);
});

test('a correction appends a new proof instead of overwriting', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    foreach (['Ana López', 'Ana L. (correction)'] as $recipient) {
        $this->actingAs($user)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
            'recipient_name' => $recipient,
            'signature' => POD_SIGNATURE,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
    }

    $this->assertDatabaseCount('proofs_of_delivery', 2);
});

test('a driver may only submit proof for their assigned trip', function () {
    Storage::fake('evidence');
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();

    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);
    $theirStop = Stop::factory()->for($team)->for($theirs)->create();

    $this->actingAs($driverUser)->post(route('driver.trips.stops.pod.store', [$team, $theirs, $theirStop]), [
        'signature' => POD_SIGNATURE,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();
});

test('pod evidence is streamed to members and hidden from other tenants', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
        'signature' => POD_SIGNATURE,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $pod = ProofOfDelivery::query()->withoutGlobalScope('team')->sole();

    $this->actingAs($user)
        ->get(route('driver.pods.signature', [$team, $pod->id]))
        ->assertOk();

    $foreignTeam = Team::factory()->create();
    $foreignPod = ProofOfDelivery::factory()->for($foreignTeam)->create();

    $this->actingAs($user)
        ->get(route('driver.pods.signature', [$team, $foreignPod->id]))
        ->assertNotFound();
});

test('a driver cannot read pod evidence from another driver trip', function () {
    Storage::fake('evidence');
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();

    $trip = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);
    $stop = Stop::factory()->for($team)->for($trip)->create();

    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner)->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
        'signature' => POD_SIGNATURE,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $pod = ProofOfDelivery::query()->withoutGlobalScope('team')->sole();

    $this->actingAs($driverUser)
        ->get(route('driver.pods.signature', [$team, $pod->id]))
        ->assertForbidden();
});

test('a warehouse member cannot read pod evidence', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $pod = ProofOfDelivery::factory()->for($team)->create();

    $this->actingAs($user)
        ->get(route('driver.pods.signature', [$team, $pod->id]))
        ->assertForbidden();
});

test('a photo over the configured limit is rejected with a validation error', function () {
    Storage::fake('evidence');
    config(['uploads.max_kilobytes' => 1]);
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
            'signature' => POD_SIGNATURE,
            'photos' => [UploadedFile::fake()->image('big.jpg')->size(5)],
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('photos.0');

    $this->assertDatabaseCount('proofs_of_delivery', 0);
});

test('a document with an unsupported type is rejected', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
            'documents' => [UploadedFile::fake()->create('notes.txt', 1, 'text/plain')],
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('documents.0');

    $this->assertDatabaseCount('proofs_of_delivery', 0);
});

test('a signature over the configured limit is rejected', function () {
    Storage::fake('evidence');
    config([
        'uploads.signature_max_characters' => 120,
        'uploads.signature_max_kilobytes' => 1,
    ]);
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [
            'signature' => 'data:image/png;base64,'.str_repeat('A', 500),
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('signature');

    $this->assertDatabaseCount('proofs_of_delivery', 0);
});

test('an oversized request body is reported instead of crashing', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    [$trip, $stop] = podSetup($team);

    $this->actingAs($user)
        ->withServerVariables(['CONTENT_LENGTH' => 50 * 1024 * 1024])
        ->withHeader('X-Inertia', 'true')
        ->post(route('driver.trips.stops.pod.store', [$team, $trip, $stop]), [])
        ->assertStatus(413);
});
