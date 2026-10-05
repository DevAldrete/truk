<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authorizes execution commands against a specific trip.
 *
 * A planner may execute any trip of the team; a driver may only execute the
 * trip assigned to their driver profile. The check never relies on a
 * client-supplied identity.
 */
trait AuthorizesTripExecution
{
    /**
     * Abort unless the current user may execute the given trip.
     */
    protected function authorizeTripExecution(Request $request, Team $team, Trip $trip): void
    {
        $user = $request->user();

        abort_if($user === null, 403);

        if ($user->hasTeamPermission($team, TeamPermission::ManageOperations)) {
            return;
        }

        abort_unless($this->isAssignedDriver($user, $team, $trip), 403);
    }

    /**
     * Determine whether the user drives the given trip.
     */
    protected function isAssignedDriver(User $user, Team $team, Trip $trip): bool
    {
        if (! $user->hasTeamPermission($team, TeamPermission::ExecuteOperations)) {
            return false;
        }

        $driver = $user->driverProfileFor($team);

        return $driver !== null && $driver->id === $trip->driver_id;
    }
}
