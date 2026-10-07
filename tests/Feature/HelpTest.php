<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a member can open the help page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Driver->value]);

    $this->actingAs($user)
        ->get(route('help', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Help'));
});

test('a non-member cannot open the help page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($user)
        ->get(route('help', $team))
        ->assertForbidden();
});
