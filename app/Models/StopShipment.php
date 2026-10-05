<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\StopShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Links a shipment to a stop, so shipments reach trips through stops.
 *
 * @property int $id
 * @property int $team_id
 * @property int $stop_id
 * @property int $shipment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stop $stop
 * @property-read Shipment $shipment
 * @property-read Team $team
 */
#[Fillable(['stop_id', 'shipment_id'])]
class StopShipment extends Model
{
    /** @use HasFactory<StopShipmentFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * Get the stop this link belongs to.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the shipment this link serves.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
