<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A problem requiring review or action, reported against a trip or stop.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $trip_id
 * @property int|null $stop_id
 * @property int|null $shipment_id
 * @property int|null $driver_id
 * @property IncidentType $type
 * @property IncidentSeverity $severity
 * @property IncidentStatus $status
 * @property string $description
 * @property Carbon $occurred_at
 * @property string|null $resolution
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property string $idempotency_key
 * @property int|null $reported_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Trip|null $trip
 * @property-read Stop|null $stop
 * @property-read Shipment|null $shipment
 * @property-read Driver|null $driver
 * @property-read User|null $reporter
 * @property-read User|null $resolver
 * @property-read Team $team
 */
#[Fillable([
    'trip_id',
    'stop_id',
    'shipment_id',
    'driver_id',
    'type',
    'severity',
    'status',
    'description',
    'occurred_at',
    'resolution',
    'resolved_by',
    'resolved_at',
    'idempotency_key',
    'reported_by',
])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the trip this incident was reported on.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the stop this incident concerns, when any.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the shipment this incident concerns, when any.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the driver this incident concerns, when any.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the login that reported this incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the login that resolved this incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentType::class,
            'severity' => IncidentSeverity::class,
            'status' => IncidentStatus::class,
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
