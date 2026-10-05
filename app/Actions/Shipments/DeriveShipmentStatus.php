<?php

namespace App\Actions\Shipments;

use App\Enums\DeliveryOutcome;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryAttempt;
use App\Models\Shipment;

/**
 * Derives a shipment's delivery status from the successful lines of its
 * delivery attempts.
 *
 * This is the single place a shipment becomes delivered or partially delivered.
 * It is idempotent: recomputing from the recorded lines yields the same status,
 * and a shipment is never marked delivered merely because a stop completed.
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
            default => $this->unsuccessfulStatus($shipment),
        };

        if ($status !== $shipment->status) {
            $shipment->update(['status' => $status]);
        }

        return $status;
    }

    /**
     * Get the status of a shipment with no delivered quantity.
     */
    protected function unsuccessfulStatus(Shipment $shipment): ShipmentStatus
    {
        $latest = DeliveryAttempt::query()
            ->where(fn ($query) => $query
                ->where('shipment_id', $shipment->id)
                ->orWhereHas('lines', fn ($lines) => $lines->where('shipment_id', $shipment->id)))
            ->latest('occurred_at')
            ->latest('id')
            ->first();

        return match ($latest?->outcome) {
            DeliveryOutcome::Failed, DeliveryOutcome::Returned => ShipmentStatus::Failed,
            default => $shipment->status,
        };
    }
}
