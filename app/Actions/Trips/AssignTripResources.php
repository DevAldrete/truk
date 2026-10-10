<?php

namespace App\Actions\Trips;

use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Team;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Assigns a driver, vehicle, and trailer to a trip.
 *
 * The resource row is locked for update before checking for overlapping trips,
 * so two dispatchers cannot book the same unit at once. Every change is
 * recorded in the append-only trip_assignments history.
 */
class AssignTripResources
{
    /**
     * @var array<string, class-string>
     */
    protected array $resources = [
        'driver_id' => Driver::class,
        'vehicle_id' => Vehicle::class,
        'trailer_id' => Trailer::class,
    ];

    /**
     * Apply the requested assignment changes.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Trip $trip, array $data): Trip
    {
        return DB::transaction(function () use ($team, $trip, $data): Trip {
            $changes = [];

            foreach ($this->resources as $column => $class) {
                $newId = $data[$column] ?? null;
                $oldId = $trip->getAttribute($column);
                $oldId = $oldId === null ? null : (int) $oldId;

                if ($newId === $oldId) {
                    continue;
                }

                if ($newId !== null) {
                    $class::query()->whereKey($newId)->lockForUpdate()->first();

                    $this->assertAvailable($trip, $column, $newId, $trip->planned_start_at, $trip->planned_end_at);
                }

                if ($oldId !== null) {
                    $trip->assignments()
                        ->whereNull('released_at')
                        ->where($column, $oldId)
                        ->update(['released_at' => now()]);
                }

                if ($newId !== null) {
                    $team->tripAssignments()->create([
                        'trip_id' => $trip->id,
                        $column => $newId,
                        'assigned_at' => now(),
                    ]);
                }

                $changes[$column] = $newId;
            }

            if ($changes !== []) {
                $trip->update($changes);
            }

            return $trip;
        });
    }

    /**
     * Ensure the resource is not already on an overlapping open trip.
     */
    protected function assertAvailable(Trip $trip, string $column, int $resourceId, ?CarbonInterface $start, ?CarbonInterface $end): void
    {
        if ($start === null || $end === null) {
            return;
        }

        $openStatuses = array_values(array_map(
            fn (TripStatus $status): string => $status->value,
            array_filter(TripStatus::cases(), fn (TripStatus $status): bool => $status->isOpen()),
        ));

        $conflict = Trip::query()
            ->whereKeyNot($trip->id)
            ->where($column, $resourceId)
            ->whereIn('status', $openStatuses)
            ->where('planned_start_at', '<', $end)
            ->where('planned_end_at', '>', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                $column => __('That resource is already assigned to an overlapping trip.'),
            ]);
        }
    }

    /**
     * Re-check every assigned resource against a proposed time window.
     *
     * Used when a trip's planned window is edited rather than its resources, so
     * a reschedule cannot silently create an overlapping booking.
     */
    public function assertResourcesWithin(Trip $trip, ?CarbonInterface $start, ?CarbonInterface $end): void
    {
        foreach (array_keys($this->resources) as $column) {
            $id = $trip->getAttribute($column);

            if ($id !== null) {
                $this->assertAvailable($trip, $column, (int) $id, $start, $end);
            }
        }
    }
}
