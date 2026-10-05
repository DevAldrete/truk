<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\TripStatus;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A planned execution using assigned resources.
 *
 * @property int $id
 * @property int $team_id
 * @property string $number
 * @property TripStatus $status
 * @property Carbon|null $planned_start_at
 * @property Carbon|null $planned_end_at
 * @property string|null $timezone
 * @property int|null $driver_id
 * @property int|null $vehicle_id
 * @property int|null $trailer_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TripAssignment> $assignments
 * @property-read Driver|null $driver
 * @property-read Vehicle|null $vehicle
 * @property-read Trailer|null $trailer
 * @property-read Team $team
 */
#[Fillable([
    'number',
    'status',
    'planned_start_at',
    'planned_end_at',
    'timezone',
    'driver_id',
    'vehicle_id',
    'trailer_id',
    'notes',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the driver assigned to this trip.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle assigned to this trip.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the trailer assigned to this trip.
     *
     * @return BelongsTo<Trailer, $this>
     */
    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Trailer::class);
    }

    /**
     * Get the assignment history of this trip.
     *
     * @return HasMany<TripAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
        ];
    }
}
