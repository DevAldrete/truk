<?php

namespace App\Http\Controllers\Loads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loads\AttachShipmentRequest;
use App\Models\Load;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LoadShipmentController extends Controller
{
    /**
     * Group a shipment into the given load.
     */
    public function store(AttachShipmentRequest $request, Team $current_team, Load $load): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $shipment = $current_team->shipments()->findOrFail($request->integer('shipment_id'));

        $shipment->update(['load_id' => $load->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $load->number])]);

        return back();
    }

    /**
     * Remove a shipment from the given load.
     */
    public function destroy(Team $current_team, Load $load, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $shipment->update(['load_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $load->number])]);

        return back();
    }
}
