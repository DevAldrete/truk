<?php

use App\Enums\TeamRole;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('a fuel expense is stored in base units with a private receipt', function () {
    Storage::fake('evidence');
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $driver = Driver::factory()->for($team)->create();
    $trip = Trip::factory()->for($team)->create(['driver_id' => $driver->id]);

    $this->actingAs($user)->post(route('driver.trips.expenses.store', [$team, $trip]), [
        'type' => 'fuel',
        'amount' => '1234.56',
        'liters' => '50.5',
        'price_per_liter' => '23.99',
        'odometer_km' => '100.5',
        'tank' => 'main',
        'vendor' => 'Pemex',
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $this->assertDatabaseCount('expenses', 1);

    $expense = Expense::query()->withoutGlobalScope('team')->sole();

    expect($expense->amount_minor)->toBe(123456)
        ->and($expense->liters_ml)->toBe(50500)
        ->and($expense->price_per_liter_minor)->toBe(2399)
        ->and($expense->odometer_meters)->toBe(100500)
        ->and($expense->currency)->toBe('MXN')
        ->and($expense->driver_id)->toBe($driver->id)
        ->and($expense->receipt_path)->not->toBeNull();

    Storage::disk('evidence')->assertExists($expense->receipt_path);
});

test('a fuel expense requires the volume', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)
        ->from(route('trips.show', [$team, $trip]))
        ->post(route('driver.trips.expenses.store', [$team, $trip]), [
            'type' => 'fuel',
            'amount' => '500',
            'idempotency_key' => (string) Str::uuid(),
        ])
        ->assertSessionHasErrors('liters');

    $this->assertDatabaseCount('expenses', 0);
});

test('a toll expense needs no fuel volume', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();

    $this->actingAs($user)->post(route('driver.trips.expenses.store', [$team, $trip]), [
        'type' => 'toll',
        'amount' => '180.00',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect();

    $this->assertDatabaseHas('expenses', [
        'trip_id' => $trip->id,
        'type' => 'toll',
        'amount_minor' => 18000,
        'liters_ml' => null,
    ]);
});

test('an expense is idempotent', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $trip = Trip::factory()->for($team)->create();
    $key = (string) Str::uuid();

    $payload = [
        'type' => 'misc',
        'amount' => '75.50',
        'idempotency_key' => $key,
    ];

    $this->actingAs($user)->post(route('driver.trips.expenses.store', [$team, $trip]), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('driver.trips.expenses.store', [$team, $trip]), $payload)->assertRedirect();

    $this->assertDatabaseCount('expenses', 1);
});

test('a driver may only record expenses on their assigned trip', function () {
    $team = Team::factory()->create();
    $driverUser = User::factory()->create();
    $team->members()->attach($driverUser, ['role' => TeamRole::Driver->value]);
    Driver::factory()->for($team)->create(['user_id' => $driverUser->id]);
    $otherDriver = Driver::factory()->for($team)->create();
    $theirs = Trip::factory()->for($team)->create(['driver_id' => $otherDriver->id]);

    $this->actingAs($driverUser)->post(route('driver.trips.expenses.store', [$team, $theirs]), [
        'type' => 'misc',
        'amount' => '10',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();
});
