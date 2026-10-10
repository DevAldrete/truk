<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A payment applied to an invoice.
 *
 * @property int $id
 * @property int $team_id
 * @property int $invoice_id
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon|null $paid_at
 * @property string|null $method
 * @property string|null $reference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 * @property-read Team $team
 */
#[Fillable(['invoice_id', 'amount_minor', 'currency', 'paid_at', 'method', 'reference'])]
class Payment extends Model
{
    use BelongsToTeam;

    /**
     * Get the invoice this payment settles.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }
}
