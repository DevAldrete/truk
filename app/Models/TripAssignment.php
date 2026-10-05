<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\TripAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only record of a resource being assigned to a trip.
 *
 * @property int $id
 * @property int $team_id
 * @property int $trip_id
 * @property int|null $driver_id
 * @property int|null $vehicle_id
 * @property int|null $trailer_id
 * @property Carbon $assigned_at
 * @property Carbon|null $released_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Trip $trip
 * @property-read Driver|null $driver
 * @property-read Vehicle|null $vehicle
 * @property-read Trailer|null $trailer
 * @property-read Team $team
 */
#[Fillable(['trip_id', 'driver_id', 'vehicle_id', 'trailer_id', 'assigned_at', 'released_at'])]
class TripAssignment extends Model
{
    /** @use HasFactory<TripAssignmentFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the trip this assignment belongs to.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the assigned driver, when this row records one.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the assigned vehicle, when this row records one.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the assigned trailer, when this row records one.
     *
     * @return BelongsTo<Trailer, $this>
     */
    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Trailer::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
