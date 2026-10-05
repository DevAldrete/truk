<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\ExpenseType;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An operational cost incurred on a trip: fuel, tolls, lodging, or misc.
 *
 * Money is integer minor units with a currency; volume is millilitres and
 * distance is metres so nothing summed is a float.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $trip_id
 * @property int|null $stop_id
 * @property int|null $driver_id
 * @property ExpenseType $type
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon $incurred_at
 * @property string|null $vendor
 * @property string|null $notes
 * @property int|null $liters_ml
 * @property int|null $price_per_liter_minor
 * @property int|null $odometer_meters
 * @property string|null $tank
 * @property string|null $receipt_path
 * @property string $idempotency_key
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Trip|null $trip
 * @property-read Stop|null $stop
 * @property-read Driver|null $driver
 * @property-read User|null $recorder
 * @property-read Team $team
 */
#[Fillable([
    'trip_id',
    'stop_id',
    'driver_id',
    'type',
    'amount_minor',
    'currency',
    'incurred_at',
    'vendor',
    'notes',
    'liters_ml',
    'price_per_liter_minor',
    'odometer_meters',
    'tank',
    'receipt_path',
    'idempotency_key',
    'recorded_by',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the trip this expense was incurred on.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the stop this expense was incurred at, when any.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the driver this expense is attributed to, when any.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the login that recorded this expense.
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
            'type' => ExpenseType::class,
            'amount_minor' => 'integer',
            'liters_ml' => 'integer',
            'price_per_liter_minor' => 'integer',
            'odometer_meters' => 'integer',
            'incurred_at' => 'datetime',
        ];
    }
}
