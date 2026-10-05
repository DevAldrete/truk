<?php

namespace App\Actions\Execution;

use App\Actions\Shipments\DerivePackageStatus;
use App\Models\ScanEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a package scan and re-derives the package's custody state.
 *
 * Idempotent on the client-generated key so a retried offline scan returns the
 * existing event rather than a duplicate.
 */
class RecordScan
{
    public function __construct(protected DerivePackageStatus $deriveStatus) {}

    /**
     * Record the scan and refresh the scanned package.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data, ?User $user = null): ScanEvent
    {
        return DB::transaction(function () use ($team, $data, $user): ScanEvent {
            $existing = $team->scanEvents()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $package = isset($data['package_id'])
                ? $team->packages()->findOrFail((int) $data['package_id'])
                : null;

            $event = $team->scanEvents()->create([
                'package_id' => $package?->id,
                'shipment_id' => $data['shipment_id'] ?? $package?->shipment_id,
                'trip_id' => $data['trip_id'] ?? null,
                'stop_id' => $data['stop_id'] ?? null,
                'type' => $data['type'],
                'occurred_at' => $data['occurred_at'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
                'recorded_by' => $user?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($package !== null) {
                $this->deriveStatus->handle($package);
            }

            return $event;
        });
    }
}
