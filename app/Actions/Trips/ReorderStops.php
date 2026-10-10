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
            $owned = $trip->stops()->orderBy('sequence')->orderBy('id')->pluck('id')->all();

            $ordered = [];

            foreach ($stopIds as $id) {
                if (in_array($id, $owned, true) && ! in_array($id, $ordered, true)) {
                    $ordered[] = $id;
                }
            }

            // Stops omitted from the payload keep their relative order after the
            // explicit ones, so the sequence always stays contiguous and unique.
            foreach ($owned as $id) {
                if (! in_array($id, $ordered, true)) {
                    $ordered[] = $id;
                }
            }

            $position = 1;

            foreach ($ordered as $id) {
                $trip->stops()->whereKey($id)->update(['sequence' => $position]);
                $position++;
            }
        });
    }
}
