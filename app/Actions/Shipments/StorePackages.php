<?php

namespace App\Actions\Shipments;

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
        return DB::transaction(function () use ($team, $shipment, $count, $weightGrams): int {
            $existing = $shipment->packages()->withTrashed()->count();

            for ($index = 1; $index <= $count; $index++) {
                $team->packages()->create([
                    'shipment_id' => $shipment->id,
                    'code' => sprintf('%s-%s-%03d', 'PKG', $shipment->id, $existing + $index),
                    'weight_grams' => $weightGrams,
                ]);
            }

            return $count;
        });
    }
}
