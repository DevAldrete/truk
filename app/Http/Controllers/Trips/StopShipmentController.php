<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Shipments\DeriveShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\AttachStopShipmentRequest;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StopShipmentController extends Controller
{
    public function __construct(private DeriveShipmentStatus $deriveStatus) {}

    /**
     * Attach a shipment to the given stop.
     */
    public function store(AttachStopShipmentRequest $request, Team $current_team, Trip $trip, Stop $stop): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $shipment = $current_team->shipments()->findOrFail($request->integer('shipment_id'));

        $current_team->stopShipments()->create([
            'stop_id' => $stop->id,
            'shipment_id' => $shipment->id,
        ]);

        $this->deriveStatus->handle($shipment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $shipment->number])]);

        return back();
    }

    /**
     * Detach a shipment from the given stop.
     */
    public function destroy(Team $current_team, Trip $trip, Stop $stop, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $stop->stopShipments()->where('shipment_id', $shipment->id)->delete();

        $this->deriveStatus->handle($shipment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $shipment->number])]);

        return back();
    }
}
