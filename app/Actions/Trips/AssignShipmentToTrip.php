<?php

namespace App\Actions\Trips;

use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Assigns a shipment to a trip from the dispatch board.
 *
 * The shipment is attached to a matching delivery stop, or a new one is created
 * for it. Shipments reach trips through stops, never directly.
 */
class AssignShipmentToTrip
{
    /**
     * Attach the shipment to the trip, idempotently.
     */
    public function handle(Team $team, Trip $trip, Shipment $shipment): void
    {
        DB::transaction(function () use ($team, $trip, $shipment): void {
            $alreadyAttached = $trip->stops()
                ->whereHas('shipments', fn ($query) => $query->whereKey($shipment->id))
                ->exists();

            if ($alreadyAttached) {
                return;
            }

            $stop = $this->matchingStop($trip, $shipment) ?? $this->createStop($team, $trip, $shipment);

            $team->stopShipments()->firstOrCreate([
                'stop_id' => $stop->id,
                'shipment_id' => $shipment->id,
            ]);
        });
    }

    /**
     * Find an existing delivery stop for the shipment's destination.
     */
    protected function matchingStop(Trip $trip, Shipment $shipment): ?Stop
    {
        return $trip->stops()
            ->where('type', StopType::Delivery->value)
            ->when(
                $shipment->delivery_location_id !== null,
                fn ($query) => $query->where('location_id', $shipment->delivery_location_id),
            )
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Create a delivery stop for the shipment's destination.
     */
    protected function createStop(Team $team, Trip $trip, Shipment $shipment): Stop
    {
        return $team->stops()->create([
            'trip_id' => $trip->id,
            'sequence' => (int) $trip->stops()->max('sequence') + 1,
            'type' => StopType::Delivery->value,
            'location_id' => $shipment->delivery_location_id,
            'location_snapshot' => $shipment->delivery_snapshot,
            'status' => StopStatus::Pending->value,
        ]);
    }
}
