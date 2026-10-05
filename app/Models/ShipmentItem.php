<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\ShipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A quantified line of a shipment.
 *
 * @property int $id
 * @property int $team_id
 * @property int $shipment_id
 * @property int|null $order_item_id
 * @property string $description
 * @property int $quantity
 * @property string $unit
 * @property int $weight_grams
 * @property int $volume_cm3
 * @property bool $hazmat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shipment $shipment
 * @property-read OrderItem|null $orderItem
 * @property-read Team $team
 */
#[Fillable([
    'shipment_id',
    'order_item_id',
    'description',
    'quantity',
    'unit',
    'weight_grams',
    'volume_cm3',
    'hazmat',
])]
class ShipmentItem extends Model
{
    /** @use HasFactory<ShipmentItemFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the shipment this line belongs to.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the order line this shipment line fulfils, when any.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hazmat' => 'boolean',
        ];
    }
}
