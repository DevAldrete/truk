<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A single pricing rule inside a rate card.
 *
 * @property int $id
 * @property int $team_id
 * @property int $rate_card_id
 * @property string $mode
 * @property int|null $origin_location_id
 * @property int|null $destination_location_id
 * @property int $amount_minor
 * @property string $currency
 * @property int|null $min_amount_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read RateCard $rateCard
 * @property-read Team $team
 */
#[Fillable([
    'rate_card_id',
    'mode',
    'origin_location_id',
    'destination_location_id',
    'amount_minor',
    'currency',
    'min_amount_minor',
])]
class Rate extends Model
{
    use BelongsToTeam, SoftDeletes;

    /**
     * Get the rate card this rule belongs to.
     *
     * @return BelongsTo<RateCard, $this>
     */
    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }
}
