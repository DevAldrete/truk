<?php

namespace App\Actions\Trips;

use App\Actions\GenerateDocumentNumber;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates trips. The trip number is assigned by the server.
 */
class SaveTrip
{
    public function __construct(private GenerateDocumentNumber $numbers) {}

    /**
     * Create a trip.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Trip
    {
        return DB::transaction(fn (): Trip => $team->trips()->create([
            'number' => $this->numbers->handle($team, Trip::withTrashed(), 'TRP'),
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
}
