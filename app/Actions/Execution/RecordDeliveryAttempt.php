<?php

namespace App\Actions\Execution;

use App\Actions\Shipments\DeriveShipmentStatus;
use App\Enums\StopStatus;
use App\Models\DeliveryAttempt;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a driver's delivery attempt at a stop.
 *
 * The command is idempotent on the client-generated key: replaying it returns
 * the existing attempt instead of duplicating lines, which makes a flaky
 * connection safe. Quantity is guarded against the planned total and a
 * shortfall is recorded as a discrepancy, never clamped.
 */
class RecordDeliveryAttempt
{
    public function __construct(protected DeriveShipmentStatus $deriveStatus) {}

    /**
     * Record the attempt and refresh the status of every affected shipment.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Stop $stop, array $data, ?User $user = null): DeliveryAttempt
    {
        return DB::transaction(function () use ($team, $stop, $data, $user): DeliveryAttempt {
            $existing = $team->deliveryAttempts()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing !== null) {
                return $existing->load('lines');
            }

            $shipmentIds = $this->distinctShipmentIds($data['lines']);

            $attempt = $team->deliveryAttempts()->create([
                'stop_id' => $stop->id,
                'shipment_id' => $data['shipment_id'] ?? (count($shipmentIds) === 1 ? $shipmentIds[0] : null),
                'outcome' => $data['outcome'],
                'failure_reason' => $data['failure_reason'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'idempotency_key' => $data['idempotency_key'],
                'recorded_by' => $user?->id,
            ]);

            $affectedShipmentIds = [];

            foreach ($data['lines'] as $line) {
                $shipment = $this->shipmentForStop($stop, (int) $line['shipment_id']);
                $quantity = (int) $line['quantity'];
                $success = (bool) $line['success'];

                $this->assertWithinPlanned($shipment, $quantity, $success);

                $team->deliveryAttemptLines()->create([
                    'delivery_attempt_id' => $attempt->id,
                    'shipment_id' => $shipment->id,
                    'shipment_item_id' => $line['shipment_item_id'] ?? null,
                    'quantity' => $quantity,
                    'success' => $success,
                    'discrepancy_reason' => $line['discrepancy_reason'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ]);

                $affectedShipmentIds[$shipment->id] = true;
            }

            $this->markArrived($stop);

            foreach (array_keys($affectedShipmentIds) as $shipmentId) {
                $this->deriveStatus->handle($team->shipments()->findOrFail($shipmentId));
            }

            return $attempt->load('lines');
        });
    }

    /**
     * Get the distinct shipment ids referenced by the attempt lines.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, int>
     */
    protected function distinctShipmentIds(array $lines): array
    {
        return array_values(array_unique(array_map(
            fn (array $line): int => (int) $line['shipment_id'],
            $lines,
        )));
    }

    /**
     * Resolve a shipment served by the stop.
     */
    protected function shipmentForStop(Stop $stop, int $shipmentId): Shipment
    {
        $shipment = $stop->shipments()->whereKey($shipmentId)->first();

        if ($shipment === null) {
            throw ValidationException::withMessages([
                'lines' => __('The shipment does not belong to this stop.'),
            ]);
        }

        return $shipment;
    }

    /**
     * Block a delivered quantity above the planned total.
     */
    protected function assertWithinPlanned(Shipment $shipment, int $quantity, bool $success): void
    {
        if (! $success || $quantity <= 0) {
            return;
        }

        $planned = max((int) $shipment->pieces, 0);

        if ($planned === 0) {
            return;
        }

        $alreadyDelivered = (int) $shipment->deliveryAttemptLines()
            ->where('success', true)
            ->sum('quantity');

        if ($alreadyDelivered + $quantity > $planned) {
            throw ValidationException::withMessages([
                'lines' => __('The delivered quantity exceeds the planned quantity for :number.', [
                    'number' => $shipment->number,
                ]),
            ]);
        }
    }

    /**
     * Move a pending stop to arrived once an attempt is recorded at it.
     */
    protected function markArrived(Stop $stop): void
    {
        if ($stop->status === StopStatus::Pending) {
            $stop->update(['status' => StopStatus::Arrived]);
        }
    }
}
