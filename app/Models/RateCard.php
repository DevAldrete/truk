<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A named set of rates for a period.
 *
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $currency
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Rate> $rates
 * @property-read Team $team
 */
#[Fillable(['name', 'currency', 'valid_from', 'valid_to'])]
class RateCard extends Model
{
    use BelongsToTeam, SoftDeletes;

    /**
     * Get the rates in this card.
     *
     * @return HasMany<Rate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }
}
