<?php

namespace App\Actions\Trips;

use App\Models\Team;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates trips. The trip number is assigned by the server.
 */
class SaveTrip
{
    /**
     * Create a trip.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Trip
    {
        return DB::transaction(fn (): Trip => $team->trips()->create([
            'number' => $this->nextNumber($team),
            ...$this->attributes($data),
        ]));
    }

    /**
     * Update a trip's planning data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Trip $trip, array $data): Trip
    {
        $trip->update($this->attributes($data));

        return $trip;
    }

    /**
     * Get the trip attributes shared by create and update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return [
            'status' => $data['status'],
            'planned_start_at' => $data['planned_start_at'] ?? null,
            'planned_end_at' => $data['planned_end_at'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Build the next trip number for the team.
     */
    protected function nextNumber(Team $team): string
    {
        $count = Trip::withTrashed()->where('team_id', $team->id)->count();

        return 'TRP-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
