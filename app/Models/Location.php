<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A pickup, delivery, or warehouse site.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $party_id
 * @property string $name
 * @property string $street
 * @property string|null $exterior_number
 * @property string|null $interior_number
 * @property string|null $neighborhood
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property string|null $references
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Party|null $party
 * @property-read Team $team
 */
#[Fillable([
    'party_id',
    'name',
    'street',
    'exterior_number',
    'interior_number',
    'neighborhood',
    'city',
    'state',
    'postal_code',
    'references',
    'latitude',
    'longitude',
    'timezone',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the party that owns this site, when it belongs to one.
     *
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
