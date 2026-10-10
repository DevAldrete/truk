<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A driver or carrier settlement over the costs of their trips.
 *
 * @property int $id
 * @property int $team_id
 * @property string $number
 * @property int|null $driver_id
 * @property int|null $carrier_party_id
 * @property SettlementStatus $status
 * @property string $currency
 * @property int $total_minor
 * @property Carbon|null $approved_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, SettlementLine> $lines
 * @property-read Driver|null $driver
 * @property-read Party|null $carrierParty
 * @property-read Team $team
 */
#[Fillable([
    'number',
    'driver_id',
    'carrier_party_id',
    'status',
    'currency',
    'total_minor',
    'approved_at',
    'paid_at',
])]
class Settlement extends Model
{
    use BelongsToTeam, SoftDeletes;

    /**
     * Get the driver being settled, when any.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the carrier party being settled, when any.
     *
     * @return BelongsTo<Party, $this>
     */
    public function carrierParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'carrier_party_id');
    }

    /**
     * Get the settlement lines.
     *
     * @return HasMany<SettlementLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SettlementLine::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SettlementStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
