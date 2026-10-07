<?php

namespace App\Actions;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Generates the next human-facing document number for a team.
 *
 * Numbers are `PREFIX-NNNNN` and are never reused, even after a soft delete.
 * We read the numerically largest existing suffix instead of counting rows, so
 * deleting a middle record cannot make the next number collide with a live one.
 * The team row is locked while we read, so two concurrent creates serialize and
 * cannot pick the same number. Callers must run inside a transaction.
 */
class GenerateDocumentNumber
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  a query on the target model, already including trashed rows
     */
    public function handle(
        Team $team,
        Builder $query,
        string $prefix,
        int $padding = 5,
    ): string {
        // Serialize concurrent creates for this team (a no-op on SQLite).
        Team::query()->whereKey($team->getKey())->lockForUpdate()->first();

        $latest = $query
            ->where('team_id', $team->getKey())
            ->orderByRaw('LENGTH(number) DESC, number DESC')
            ->value('number');

        $sequence = is_string($latest)
            ? ((int) substr($latest, strlen($prefix) + 1)) + 1
            : 1;

        return $prefix.'-'.str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
    }
}
