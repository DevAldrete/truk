<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single line of a settlement, sourced from a trip and/or an expense.
 *
 * @property int $id
 * @property int $team_id
 * @property int $settlement_id
 * @property int|null $trip_id
 * @property int|null $expense_id
 * @property string $description
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Settlement $settlement
 * @property-read Trip|null $trip
 * @property-read Expense|null $expense
 * @property-read Team $team
 */
#[Fillable([
    'settlement_id',
    'trip_id',
    'expense_id',
    'description',
    'amount_minor',
    'currency',
])]
class SettlementLine extends Model
{
    use BelongsToTeam;

    /**
     * Get the settlement this line belongs to.
     *
     * @return BelongsTo<Settlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    /**
     * Get the trip this line covers, when any.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the expense this line reimburses, when any.
     *
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
