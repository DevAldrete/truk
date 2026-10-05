<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\TrailerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A non-motorised unit pulled by a vehicle, own fleet or subcontracted.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $carrier_party_id
 * @property string $name
 * @property string $plate
 * @property string $configuration
 * @property int $max_payload_grams
 * @property int|null $max_volume_cm3
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read float $max_payload_kg
 * @property-read float|null $max_volume_m3
 * @property-read Party|null $carrierParty
 * @property-read Team $team
 */
#[Fillable(['carrier_party_id', 'name', 'plate', 'configuration', 'max_payload_grams', 'max_volume_cm3'])]
class Trailer extends Model
{
    /** @use HasFactory<TrailerFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the subcontractor that owns this trailer, when not on the own fleet.
     *
     * @return BelongsTo<Party, $this>
     */
    public function carrierParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'carrier_party_id');
    }

    /**
     * Get the payload in kilograms, for display only.
     *
     * @return Attribute<float, never>
     */
    protected function maxPayloadKg(): Attribute
    {
        return Attribute::make(get: fn (): float => $this->max_payload_grams / 1000);
    }

    /**
     * Get the volume in cubic metres, for display only.
     *
     * @return Attribute<float|null, never>
     */
    protected function maxVolumeM3(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->max_volume_cm3 === null ? null : $this->max_volume_cm3 / 1000000,
        );
    }
}
