<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('an owner can create a staff account with an organization login', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner)
        ->post(route('teams.members.store', $team), [
            'name' => 'Juan Pérez',
            'username' => 'juan',
            'password' => 'secret123',
            'role' => TeamRole::Dispatcher->value,
        ])
        ->assertRedirect(route('teams.edit', $team));

    $user = User::query()->where('username', 'acme/juan')->sole();

    expect($user->email)->toBeNull()
        ->and($user->belongsToTeam($team))->toBeTrue()
        ->and($team->members()->where('user_id', $user->id)->first()->pivot->role->value)->toBe(TeamRole::Dispatcher->value);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk();
});

test('a staff account can log in with its username', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create([
        'name' => 'Juan Pérez',
        'username' => 'acme/juan',
        'email' => null,
    ]);
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->post(route('login.store'), [
        'email' => 'acme/juan',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('an email address still logs in', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('a duplicate username is rejected', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    User::factory()->create(['username' => 'acme/juan']);

    $this->actingAs($owner)
        ->from(route('teams.edit', $team))
        ->post(route('teams.members.store', $team), [
            'name' => 'Otro Juan',
            'username' => 'juan',
            'password' => 'secret123',
            'role' => TeamRole::Member->value,
        ])
        ->assertSessionHasErrors('login');
});

test('a dispatcher cannot create a staff account', function () {
    $dispatcher = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $team->members()->attach($dispatcher, ['role' => TeamRole::Dispatcher->value]);

    $this->actingAs($dispatcher)
        ->post(route('teams.members.store', $team), [
            'name' => 'Juan Pérez',
            'username' => 'juan',
            'password' => 'secret123',
            'role' => TeamRole::Member->value,
        ])
        ->assertForbidden();
});
