<?php

namespace App\Actions\Shipments;

use App\Models\Package;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Adds packages to a shipment, continuing the shipment's package numbering so
 * codes stay unique per team.
 */
class StorePackages
{
    /**
     * Add the given number of packages to the shipment.
     */
    public function handle(Team $team, Shipment $shipment, int $count, ?int $weightGrams = null): int
    {
        if ($count < 1) {
            return 0;
        }

        return DB::transaction(function () use ($team, $shipment, $count, $weightGrams): int {
            // Lock the shipment so concurrent additions cannot collide on numbering.
            $shipment->newQuery()->whereKey($shipment->id)->lockForUpdate()->first();

            $existing = $this->latestSequence($shipment);
            $now = now();
            $rows = [];

            for ($index = 1; $index <= $count; $index++) {
                $rows[] = [
                    'team_id' => $team->id,
                    'shipment_id' => $shipment->id,
                    'code' => sprintf('%s-%s-%03d', 'PKG', $shipment->id, $existing + $index),
                    'weight_grams' => $weightGrams,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Package::query()->insert($rows);

            return $count;
        });
    }

    /**
     * The highest package sequence already used on the shipment.
     *
     * Reading the highest suffix (including trashed rows) rather than counting
     * rows keeps numbering monotonic even after a hard delete.
     */
    protected function latestSequence(Shipment $shipment): int
    {
        $latest = Package::withTrashed()
            ->where('shipment_id', $shipment->id)
            ->orderByRaw('LENGTH(code) DESC, code DESC')
            ->value('code');

        return is_string($latest)
            ? (int) substr($latest, strrpos($latest, '-') + 1)
            : 0;
    }
}
