<?php

use App\Enums\IncidentStatus;
use App\Enums\TeamRole;
use App\Models\Incident;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the incident board lists the current team incidents', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Incident::factory()->for($team)->create(['description' => 'Flat tire on the road']);
    Incident::factory()->for(Team::factory()->create())->create(['description' => 'Another team problem']);

    $this->actingAs($user)
        ->get(route('incidents.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('incidents/Index')
            ->has('incidents.data', 1)
            ->where('incidents.data.0.description', 'Flat tire on the road'));
});

test('the incident board can be filtered by status', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Incident::factory()->for($team)->create(['status' => IncidentStatus::Open->value]);
    Incident::factory()->for($team)->create([
        'status' => IncidentStatus::Resolved->value,
        'resolution' => 'Handled',
        'resolved_by' => $user->id,
        'resolved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('incidents.index', [$team, 'status' => 'open']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.status', IncidentStatus::Open->value)
            ->where('filters.status', IncidentStatus::Open->value));
});

test('the incident board reports the open incident count', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Incident::factory()->for($team)->create(['status' => IncidentStatus::Open->value]);
    Incident::factory()->for($team)->create(['status' => IncidentStatus::Investigating->value]);
    Incident::factory()->for($team)->create([
        'status' => IncidentStatus::Resolved->value,
        'resolution' => 'Done',
        'resolved_by' => $user->id,
        'resolved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('incidents.index', $team))
        ->assertInertia(fn (Assert $page) => $page->where('counts.open', 2));
});

test('an incident detail is not reachable from another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $foreignIncident = Incident::factory()->for(Team::factory()->create())->create();

    $this->actingAs($user)
        ->get(route('incidents.show', [$team, $foreignIncident]))
        ->assertNotFound();
});

test('a warehouse member can view but not resolve incidents', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Warehouse->value]);
    $incident = Incident::factory()->for($team)->create();

    $this->actingAs($user)
        ->get(route('incidents.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can.manage', false));

    $this->actingAs($user)
        ->patch(route('incidents.update', [$team, $incident]), [
            'status' => IncidentStatus::Resolved->value,
            'resolution' => 'Done.',
        ])
        ->assertForbidden();
});
