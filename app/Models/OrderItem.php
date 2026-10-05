<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A quantified line of an order.
 *
 * @property int $id
 * @property int $team_id
 * @property int $order_id
 * @property string $description
 * @property int $quantity
 * @property string $unit
 * @property int $weight_grams
 * @property int $volume_cm3
 * @property bool $hazmat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Team $team
 */
#[Fillable(['order_id', 'description', 'quantity', 'unit', 'weight_grams', 'volume_cm3', 'hazmat'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the order this line belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
