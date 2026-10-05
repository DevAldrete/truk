<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\AssignTripResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\AssignTripResourcesRequest;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TripResourceController extends Controller
{
    /**
     * Assign the driver, vehicle, and trailer of the given trip.
     */
    public function update(
        AssignTripResourcesRequest $request,
        Team $current_team,
        Trip $trip,
        AssignTripResources $assign,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $assign->handle($current_team, $trip, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }
}
