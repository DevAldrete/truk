<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A commercial request to move goods.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $customer_party_id
 * @property string $number
 * @property OrderStatus $status
 * @property string $currency
 * @property Carbon|null $requested_pickup_at
 * @property Carbon|null $requested_delivery_at
 * @property string|null $notes
 * @property string|null $customer_name
 * @property string|null $customer_rfc
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, Shipment> $shipments
 * @property-read Party|null $customerParty
 * @property-read Team $team
 */
#[Fillable([
    'customer_party_id',
    'number',
    'status',
    'currency',
    'requested_pickup_at',
    'requested_delivery_at',
    'notes',
    'customer_name',
    'customer_rfc',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the priced lines of this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the shipments created from this order.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get the customer this order belongs to.
     *
     * @return BelongsTo<Party, $this>
     */
    public function customerParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_party_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'requested_pickup_at' => 'datetime',
            'requested_delivery_at' => 'datetime',
        ];
    }
}
