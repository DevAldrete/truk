<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\AssignShipmentToTrip;
use App\Actions\Trips\UnassignShipmentFromTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\AssignTripShipmentRequest;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TripShipmentController extends Controller
{
    /**
     * Assign a shipment to the given trip from the dispatch board.
     */
    public function store(
        AssignTripShipmentRequest $request,
        Team $current_team,
        Trip $trip,
        AssignShipmentToTrip $assign,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $shipment = $current_team->shipments()->findOrFail($request->integer('shipment_id'));

        $assign->handle($current_team, $trip, $shipment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }

    /**
     * Remove a shipment from the given trip.
     */
    public function destroy(
        Team $current_team,
        Trip $trip,
        Shipment $shipment,
        UnassignShipmentFromTrip $unassign,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $unassign->handle($trip, $shipment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }
}
