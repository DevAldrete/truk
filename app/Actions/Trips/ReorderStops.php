<?php

namespace App\Actions\Trips;

use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Re-sequences the stops of a trip from an ordered list of stop ids. Ids that
 * do not belong to the trip are ignored.
 */
class ReorderStops
{
    /**
     * Apply the given order to the trip's stops.
     *
     * @param  array<int, int>  $stopIds
     */
    public function handle(Trip $trip, array $stopIds): void
    {
        DB::transaction(function () use ($trip, $stopIds): void {
            $owned = $trip->stops()->pluck('id')->all();
            $position = 1;

            foreach ($stopIds as $id) {
                if (! in_array($id, $owned, true)) {
                    continue;
                }

                $trip->stops()->whereKey($id)->update(['sequence' => $position]);
                $position++;
            }
        });
    }
}
