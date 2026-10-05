<?php

namespace App\Actions\Trips;

use App\Models\Location;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates the stops of a trip. New stops are appended to the
 * sequence; the address is snapshotted so later edits to the site do not
 * rewrite planned history.
 */
class SaveStop
{
    /**
     * Create a stop at the end of the trip's sequence.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, Trip $trip, array $data): Stop
    {
        return DB::transaction(function () use ($team, $trip, $data): Stop {
            $location = $this->location($team, $data['location_id'] ?? null);
            $sequence = (int) $trip->stops()->max('sequence') + 1;

            return $team->stops()->create([
                'trip_id' => $trip->id,
                'sequence' => $sequence,
                'type' => $data['type'],
                'location_id' => $location?->id,
                'location_snapshot' => $this->snapshot($location),
                'planned_at' => $data['planned_at'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Update a stop.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Team $team, Stop $stop, array $data): Stop
    {
        $location = $this->location($team, $data['location_id'] ?? null);

        $stop->update([
            'type' => $data['type'],
            'location_id' => $location?->id,
            'location_snapshot' => $this->snapshot($location),
            'planned_at' => $data['planned_at'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $stop;
    }

    /**
     * Resolve a location of the team, when one was chosen.
     */
    protected function location(Team $team, ?int $locationId): ?Location
    {
        return $locationId === null
            ? null
            : $team->locations()->find($locationId);
    }

    /**
     * Freeze the address data of a location for the stop snapshot.
     *
     * @return array<string, mixed>|null
     */
    protected function snapshot(?Location $location): ?array
    {
        if ($location === null) {
            return null;
        }

        return [
            'name' => $location->name,
            'street' => $location->street,
            'exterior_number' => $location->exterior_number,
            'interior_number' => $location->interior_number,
            'neighborhood' => $location->neighborhood,
            'city' => $location->city,
            'state' => $location->state,
            'postal_code' => $location->postal_code,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ];
    }
}
