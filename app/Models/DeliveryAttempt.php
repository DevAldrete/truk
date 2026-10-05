<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryOutcome;
use Database\Factories\DeliveryAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A specific attempt to complete a delivery at a stop.
 *
 * An attempt records what happened at one moment; the shipment status is
 * derived from the successful lines of every attempt, never from the attempt
 * outcome itself.
 *
 * @property int $id
 * @property int $team_id
 * @property int $stop_id
 * @property int|null $shipment_id
 * @property DeliveryOutcome $outcome
 * @property DeliveryFailureReason|null $failure_reason
 * @property string|null $recipient_name
 * @property string|null $notes
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon $occurred_at
 * @property string $idempotency_key
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DeliveryAttemptLine> $lines
 * @property-read Stop $stop
 * @property-read Shipment|null $shipment
 * @property-read User|null $recorder
 * @property-read Team $team
 */
#[Fillable([
    'stop_id',
    'shipment_id',
    'outcome',
    'failure_reason',
    'recipient_name',
    'notes',
    'latitude',
    'longitude',
    'occurred_at',
    'idempotency_key',
    'recorded_by',
])]
class DeliveryAttempt extends Model
{
    /** @use HasFactory<DeliveryAttemptFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the lines that quantify this attempt.
     *
     * @return HasMany<DeliveryAttemptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryAttemptLine::class);
    }

    /**
     * Get the stop this attempt was recorded at.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the shipment this attempt targeted, when it was for a single one.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the login that recorded this attempt.
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
            'outcome' => DeliveryOutcome::class,
            'failure_reason' => DeliveryFailureReason::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'occurred_at' => 'datetime',
        ];
    }
}
