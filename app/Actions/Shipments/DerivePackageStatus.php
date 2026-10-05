<?php

namespace App\Actions\Shipments;

use App\Enums\PackageStatus;
use App\Models\Package;

/**
 * Derives a package's custody state from its scan events.
 *
 * Scans are replayed in `occurred_at` order through the allowed-transition
 * map, so an out-of-order offline scan does not move the package backwards and
 * an invalid jump is ignored rather than persisted.
 */
class DerivePackageStatus
{
    /**
     * Recompute and persist the package status.
     */
    public function handle(Package $package): PackageStatus
    {
        $events = $package->scans()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $status = PackageStatus::Created;

        foreach ($events as $event) {
            $target = $event->type->packageStatus();

            if ($status->canTransitionTo($target)) {
                $status = $target;
            }
        }

        if ($status !== $package->status) {
            $package->update(['status' => $status]);
        }

        return $status;
    }
}
