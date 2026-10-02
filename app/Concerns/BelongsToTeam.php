<?php

namespace App\Concerns;

use App\Data\TeamContext;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Scopes a model to the team held by the current {@see TeamContext}.
 *
 * A model using this trait has a `team_id` column that must not be mass
 * assignable: this trait is the only thing that sets it. Queries run without a
 * team context throw instead of quietly returning every team's rows, so
 * background work has to opt in through {@see TeamContext::run()}.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToTeam
{
    /**
     * Bootstrap the trait for the model.
     */
    public static function bootBelongsToTeam(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('team_id') !== null) {
                return;
            }

            $teamId = static::teamContext()->id() ?? throw new LogicException(sprintf(
                'Cannot create [%s] without an active team context.',
                $model::class,
            ));

            $model->setAttribute('team_id', $teamId);
        });

        static::addGlobalScope('team', function (Builder $builder): void {
            $teamId = static::teamContext()->id() ?? throw new LogicException(sprintf(
                'Cannot query [%s] without an active team context. Wrap the query in TeamContext::run().',
                $builder->getModel()::class,
            ));

            $builder->where($builder->getModel()->qualifyColumn('team_id'), $teamId);
        });
    }

    /**
     * Get the team that owns the record.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the team context for the current request, job, or command.
     */
    protected static function teamContext(): TeamContext
    {
        return app(TeamContext::class);
    }
}
