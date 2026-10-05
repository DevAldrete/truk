<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\ShipmentStatus;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The operational unit representing goods to be transported.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $order_id
 * @property int|null $load_id
 * @property string $number
 * @property ShipmentStatus $status
 * @property string $currency
 * @property int|null $pickup_location_id
 * @property int|null $delivery_location_id
 * @property array<string, mixed>|null $pickup_snapshot
 * @property array<string, mixed>|null $delivery_snapshot
 * @property string|null $customer_name
 * @property int $weight_grams
 * @property int $volume_cm3
 * @property int $pieces
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ShipmentItem> $items
 * @property-read Collection<int, Package> $packages
 * @property-read Order|null $order
 * @property-read Load|null $loadGroup
 * @property-read Location|null $pickupLocation
 * @property-read Location|null $deliveryLocation
 * @property-read Team $team
 */
#[Fillable([
    'order_id',
    'load_id',
    'number',
    'status',
    'currency',
    'pickup_location_id',
    'delivery_location_id',
    'pickup_snapshot',
    'delivery_snapshot',
    'customer_name',
    'weight_grams',
    'volume_cm3',
    'pieces',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the goods lines of this shipment.
     *
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Get the physical packages of this shipment.
     *
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Get the delivery attempts recorded against this shipment.
     *
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    /**
     * Get the quantified delivery lines that account for this shipment.
     *
     * @return HasMany<DeliveryAttemptLine, $this>
     */
    public function deliveryAttemptLines(): HasMany
    {
        return $this->hasMany(DeliveryAttemptLine::class);
    }

    /**
     * Get the order this shipment was created from, when any.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the load this shipment is grouped into, when any.
     *
     * Named `loadGroup` because `load()` is reserved by Eloquent.
     *
     * @return BelongsTo<Load, $this>
     */
    public function loadGroup(): BelongsTo
    {
        return $this->belongsTo(Load::class, 'load_id');
    }

    /**
     * Get the pickup site.
     *
     * @return BelongsTo<Location, $this>
     */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'pickup_location_id');
    }

    /**
     * Get the delivery site.
     *
     * @return BelongsTo<Location, $this>
     */
    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_location_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'pickup_snapshot' => 'array',
            'delivery_snapshot' => 'array',
        ];
    }
}
