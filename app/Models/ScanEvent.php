<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\ScanType;
use Database\Factories\ScanEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A package or asset movement / custody event.
 *
 * Events are append-only and ordered by `occurred_at`, not by insert order, so
 * a delayed offline scan still resolves the package's true custody state.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $package_id
 * @property int|null $shipment_id
 * @property int|null $trip_id
 * @property int|null $stop_id
 * @property ScanType $type
 * @property Carbon $occurred_at
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $idempotency_key
 * @property int|null $recorded_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package|null $package
 * @property-read Shipment|null $shipment
 * @property-read Trip|null $trip
 * @property-read Stop|null $stop
 * @property-read User|null $recorder
 * @property-read Team $team
 */
#[Fillable([
    'package_id',
    'shipment_id',
    'trip_id',
    'stop_id',
    'type',
    'occurred_at',
    'latitude',
    'longitude',
    'idempotency_key',
    'recorded_by',
    'notes',
])]
class ScanEvent extends Model
{
    /** @use HasFactory<ScanEventFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the package this event concerns, when any.
     *
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the shipment this event concerns, when any.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the trip this event was recorded on, when any.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the stop this event was recorded at, when any.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the login that recorded this event.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ScanType::class,
            'occurred_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
