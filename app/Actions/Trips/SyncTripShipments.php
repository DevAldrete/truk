<?php

namespace App\Actions\Trips;

use App\Actions\Shipments\DeriveShipmentStatus;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Trip;

/**
 * Re-derives the status of every shipment served by a trip.
 *
 * Called when a trip is dispatched, moves, completes, or is cancelled, so the
 * shipments on its stops reflect the road state without any manual edit.
 */
class SyncTripShipments
{
    public function __construct(private DeriveShipmentStatus $deriveStatus) {}

    /**
     * Recompute the status of each distinct shipment on the trip's stops.
     */
    public function handle(Trip $trip): void
    {
        $trip->stops()
            ->with('shipments')
            ->get()
            ->flatMap(fn (Stop $stop) => $stop->shipments)
            ->unique('id')
            ->each(fn (Shipment $shipment) => $this->deriveStatus->handle($shipment));
    }
}
