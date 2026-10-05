<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\StopStatus;
use App\Enums\StopType;
use Database\Factories\StopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A planned pickup, delivery, or other action within a trip.
 *
 * @property int $id
 * @property int $team_id
 * @property int $trip_id
 * @property int $sequence
 * @property StopType $type
 * @property int|null $location_id
 * @property array<string, mixed>|null $location_snapshot
 * @property Carbon|null $planned_at
 * @property StopStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Trip $trip
 * @property-read Location|null $location
 * @property-read Collection<int, StopShipment> $stopShipments
 * @property-read Collection<int, Shipment> $shipments
 * @property-read Collection<int, DeliveryAttempt> $deliveryAttempts
 * @property-read Team $team
 */
#[Fillable([
    'trip_id',
    'sequence',
    'type',
    'location_id',
    'location_snapshot',
    'planned_at',
    'status',
    'notes',
])]
class Stop extends Model
{
    /** @use HasFactory<StopFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the trip this stop belongs to.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the site served by this stop, when any.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the shipment links of this stop.
     *
     * @return HasMany<StopShipment, $this>
     */
    public function stopShipments(): HasMany
    {
        return $this->hasMany(StopShipment::class);
    }

    /**
     * Get the shipments served by this stop.
     *
     * @return BelongsToMany<Shipment, $this>
     */
    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(
            Shipment::class,
            'stop_shipments',
            'stop_id',
            'shipment_id',
        )->withTimestamps();
    }

    /**
     * Get the delivery attempts recorded at this stop.
     *
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StopType::class,
            'status' => StopStatus::class,
            'location_snapshot' => 'array',
            'planned_at' => 'datetime',
        ];
    }
}
