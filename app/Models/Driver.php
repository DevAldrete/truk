<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A person who operates a vehicle, either on the own fleet or for a carrier.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $carrier_party_id
 * @property string $name
 * @property string $phone
 * @property string|null $license_number
 * @property Carbon|null $license_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Party|null $carrierParty
 * @property-read Team $team
 */
#[Fillable(['carrier_party_id', 'name', 'phone', 'license_number', 'license_expires_at'])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the subcontractor this driver works for, when not on the own fleet.
     *
     * @return BelongsTo<Party, $this>
     */
    public function carrierParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'carrier_party_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
        ];
    }
}
