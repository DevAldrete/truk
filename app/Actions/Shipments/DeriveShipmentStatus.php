<?php

namespace App\Actions\Shipments;

use App\Enums\DeliveryOutcome;
use App\Enums\ShipmentStatus;
use App\Enums\TripStatus;
use App\Models\DeliveryAttempt;
use App\Models\Shipment;
use App\Models\Trip;
use Illuminate\Support\Collection;

/**
 * Derives a shipment's status from what actually happened to it.
 *
 * Delivery is derived from the successful lines of its delivery attempts; the
 * road state (dispatched / in transit) is derived from the trips that serve it
 * through stops. This is the single place a shipment's status is written.
 *
 * It is idempotent: recomputing yields the same status, and a shipment is never
 * marked delivered merely because a stop or trip completed.
 */
class DeriveShipmentStatus
{
    /**
     * Recompute and persist the shipment status.
     */
    public function handle(Shipment $shipment): ShipmentStatus
    {
        $delivered = (int) $shipment->deliveryAttemptLines()
            ->where('success', true)
            ->sum('quantity');

        $planned = max((int) $shipment->pieces, 0);

        $status = match (true) {
            $delivered > 0 && $planned > 0 && $delivered < $planned => ShipmentStatus::PartiallyDelivered,
            $delivered > 0 => ShipmentStatus::Delivered,
            default => $this->undeliveredStatus($shipment),
        };

        if ($status !== $shipment->status) {
            $shipment->update(['status' => $status]);
        }

        return $status;
    }

    /**
     * Get the status of a shipment with no delivered quantity.
     */
    protected function undeliveredStatus(Shipment $shipment): ShipmentStatus
    {
        if ($this->lastAttemptFailed($shipment)) {
            return ShipmentStatus::Failed;
        }

        $trips = $this->servingTrips($shipment);

        if ($trips->contains(fn (Trip $trip): bool => $trip->status === TripStatus::InTransit)) {
            return ShipmentStatus::InTransit;
        }

        if ($trips->contains(fn (Trip $trip): bool => $trip->status === TripStatus::Dispatched)) {
            return ShipmentStatus::Dispatched;
        }

        if ($trips->contains(fn (Trip $trip): bool => $trip->status === TripStatus::Planned)) {
            return ShipmentStatus::Planned;
        }

        // Only cancelled trips (or none) remain. A shipment that had left the
        // yard is a failure to re-plan; one that never left returns to the pool.
        if (
            $trips->isNotEmpty()
            && in_array($shipment->status, [ShipmentStatus::Dispatched, ShipmentStatus::InTransit], true)
        ) {
            return ShipmentStatus::Failed;
        }

        return ShipmentStatus::Planned;
    }

    /**
     * Determine whether the shipment's most recent attempt failed or returned.
     */
    protected function lastAttemptFailed(Shipment $shipment): bool
    {
        $latest = DeliveryAttempt::query()
            ->where(fn ($query) => $query
                ->where('shipment_id', $shipment->id)
                ->orWhereHas('lines', fn ($lines) => $lines->where('shipment_id', $shipment->id)))
            ->latest('occurred_at')
            ->latest('id')
            ->first();

        return in_array($latest?->outcome, [DeliveryOutcome::Failed, DeliveryOutcome::Returned], true);
    }

    /**
     * Get the trips that serve the shipment through their stops.
     *
     * @return Collection<int, Trip>
     */
    protected function servingTrips(Shipment $shipment): Collection
    {
        return Trip::query()
            ->whereHas('stops.shipments', fn ($query) => $query->whereKey($shipment->id))
            ->get();
    }
}
