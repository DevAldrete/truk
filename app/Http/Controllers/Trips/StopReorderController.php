<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\ReorderStops;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\ReorderStopsRequest;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StopReorderController extends Controller
{
    /**
     * Re-sequence the stops of the given trip.
     */
    public function update(ReorderStopsRequest $request, Team $current_team, Trip $trip, ReorderStops $reorder): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        /** @var array<int, int> $stopIds */
        $stopIds = $request->input('stop_ids', []);

        $reorder->handle($trip, $stopIds);

        return back();
    }
}
