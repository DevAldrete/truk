<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\BillingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A customer invoice. Money is stored as integer minor units with a currency.
 *
 * @property int $id
 * @property int $team_id
 * @property string $number
 * @property int|null $customer_party_id
 * @property int|null $trip_id
 * @property BillingStatus $status
 * @property string $currency
 * @property int $subtotal_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property string|null $cfdi_uuid
 * @property Carbon|null $issued_at
 * @property Carbon|null $due_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, InvoiceLine> $lines
 * @property-read Collection<int, Payment> $payments
 * @property-read Party|null $customerParty
 * @property-read Trip|null $trip
 * @property-read Team $team
 */
#[Fillable([
    'number',
    'customer_party_id',
    'trip_id',
    'status',
    'currency',
    'subtotal_minor',
    'tax_minor',
    'total_minor',
    'cfdi_uuid',
    'issued_at',
    'due_at',
])]
class Invoice extends Model
{
    use BelongsToTeam, SoftDeletes;

    /**
     * Get the customer party billed.
     *
     * @return BelongsTo<Party, $this>
     */
    public function customerParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_party_id');
    }

    /**
     * Get the trip invoiced, when any.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the invoice lines.
     *
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get the payments applied to this invoice.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BillingStatus::class,
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }
}
