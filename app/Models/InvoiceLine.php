<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single line of an invoice.
 *
 * @property int $id
 * @property int $team_id
 * @property int $invoice_id
 * @property string $description
 * @property int $quantity
 * @property int $unit_amount_minor
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 * @property-read Team $team
 */
#[Fillable([
    'invoice_id',
    'description',
    'quantity',
    'unit_amount_minor',
    'amount_minor',
    'currency',
])]
class InvoiceLine extends Model
{
    use BelongsToTeam;

    /**
     * Get the invoice this line belongs to.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
