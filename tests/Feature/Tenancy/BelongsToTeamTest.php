<?php

use App\Concerns\BelongsToTeam;
use App\Data\TeamContext;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * A stand-in for the operational models that will use the trait.
 */
class TenantedRecord extends Model
{
    use BelongsToTeam;

    protected $table = 'tenanted_records';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('tenanted_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('team_id');
        $table->string('name');
    });
});

afterEach(function () {
    Schema::dropIfExists('tenanted_records');
});

test('records are scoped to the team in context', function () {
    $context = app(TeamContext::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    $context->run($team->id, fn () => TenantedRecord::create(['name' => 'Mine']));
    $context->run($otherTeam->id, fn () => TenantedRecord::create(['name' => 'Theirs']));

    $context->run($team->id, function () {
        expect(TenantedRecord::pluck('name')->all())->toBe(['Mine']);
    });
});

test('creating a record fills the team from the context', function () {
    $context = app(TeamContext::class);
    $team = Team::factory()->create();

    $record = $context->run($team->id, fn () => TenantedRecord::create(['name' => 'Mine']));

    expect($record->team_id)->toBe($team->id);
});

test('an explicit team id is kept', function () {
    $context = app(TeamContext::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    $record = $context->run($team->id, fn () => TenantedRecord::create([
        'name' => 'Theirs',
        'team_id' => $otherTeam->id,
    ]));

    expect($record->team_id)->toBe($otherTeam->id);
});

test('querying without a team context throws', function () {
    $context = app(TeamContext::class);

    expect(fn () => TenantedRecord::count())->toThrow(LogicException::class);
});

test('creating without a team context throws', function () {
    expect(fn () => TenantedRecord::create(['name' => 'Orphan']))->toThrow(LogicException::class);
});

test('the previous context is restored after a scoped run', function () {
    $context = app(TeamContext::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    $context->set($team->id);
    $context->run($otherTeam->id, fn () => null);

    expect($context->id())->toBe($team->id);
});
