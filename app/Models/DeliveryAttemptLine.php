<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\DeliveryAttemptLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A quantified line of a delivery attempt.
 *
 * Delivered quantity is the sum of successful lines across every attempt for a
 * shipment; a shortfall is recorded as a discrepancy instead of being clamped.
 *
 * @property int $id
 * @property int $team_id
 * @property int $delivery_attempt_id
 * @property int $shipment_id
 * @property int|null $shipment_item_id
 * @property int $quantity
 * @property bool $success
 * @property string|null $discrepancy_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DeliveryAttempt $attempt
 * @property-read Shipment $shipment
 * @property-read ShipmentItem|null $shipmentItem
 * @property-read Team $team
 */
#[Fillable([
    'delivery_attempt_id',
    'shipment_id',
    'shipment_item_id',
    'quantity',
    'success',
    'discrepancy_reason',
    'notes',
])]
class DeliveryAttemptLine extends Model
{
    /** @use HasFactory<DeliveryAttemptLineFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the attempt this line belongs to.
     *
     * @return BelongsTo<DeliveryAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DeliveryAttempt::class);
    }

    /**
     * Get the shipment this line accounts for.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the shipment line this quantity belongs to, when itemised.
     *
     * @return BelongsTo<ShipmentItem, $this>
     */
    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }
}
