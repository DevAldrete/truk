<?php

namespace App\Actions\Trips;

use App\Actions\Shipments\DeriveShipmentStatus;
use App\Models\Shipment;
use App\Models\StopShipment;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Removes a shipment from a trip by detaching it from every stop of the trip.
 */
class UnassignShipmentFromTrip
{
    public function __construct(private DeriveShipmentStatus $deriveStatus) {}

    /**
     * Detach the shipment from the trip's stops.
     */
    public function handle(Trip $trip, Shipment $shipment): void
    {
        DB::transaction(function () use ($trip, $shipment): void {
            $stopIds = $trip->stops()->pluck('id');

            StopShipment::query()
                ->whereIn('stop_id', $stopIds)
                ->where('shipment_id', $shipment->id)
                ->delete();

            $this->deriveStatus->handle($shipment);
        });
    }
}
