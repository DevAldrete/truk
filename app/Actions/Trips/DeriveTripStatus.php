<?php

namespace App\Actions\Trips;

use App\Enums\StopStatus;
use App\Enums\TripStatus;
use App\Models\Stop;
use App\Models\Trip;

/**
 * Advances a trip's status from the driver's actions on its stops.
 *
 * It only moves a trip forward from `dispatched` to `in_transit` once a stop
 * has been worked, and to `completed` once every stop is terminal. It never
 * dispatches a planned trip (that stays a deliberate, gated action) and never
 * cancels one. Manual transitions remain permissioned in the planner.
 */
class DeriveTripStatus
{
    public function __construct(private SyncTripShipments $syncShipments) {}

    /**
     * Recompute and persist the trip status from its stops.
     */
    public function handle(Trip $trip): Trip
    {
        if (! in_array($trip->status, [TripStatus::Dispatched, TripStatus::InTransit], true)) {
            return $trip;
        }

        $stops = $trip->stops()->get();

        if ($stops->isEmpty()) {
            return $trip;
        }

        $terminal = [StopStatus::Completed, StopStatus::Failed, StopStatus::Skipped];

        $hasStarted = $stops->contains(fn (Stop $stop): bool => $stop->status !== StopStatus::Pending);
        $allTerminal = $stops->every(fn (Stop $stop): bool => in_array($stop->status, $terminal, true));

        $target = $trip->status;

        if ($hasStarted && $target === TripStatus::Dispatched) {
            $target = TripStatus::InTransit;
        }

        if ($allTerminal && $target === TripStatus::InTransit) {
            $target = TripStatus::Completed;
        }

        if ($target !== $trip->status) {
            $trip->update(['status' => $target->value]);
            $this->syncShipments->handle($trip);
        }

        return $trip;
    }
}
