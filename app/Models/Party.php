<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\PartyType;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A business relationship: a customer, a carrier, or a supplier.
 *
 * @property int $id
 * @property int $team_id
 * @property PartyType $type
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $rfc
 * @property string|null $tax_regime
 * @property string|null $cfdi_use
 * @property string|null $tax_zip_code
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $contacts_count
 * @property-read int|null $locations_count
 * @property-read Collection<int, PartyContact> $contacts
 * @property-read Collection<int, Location> $locations
 * @property-read Team $team
 */
#[Fillable(['type', 'name', 'legal_name', 'rfc', 'tax_regime', 'cfdi_use', 'tax_zip_code', 'email', 'phone'])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the people to contact at this party.
     *
     * @return HasMany<PartyContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    /**
     * Get the pickup and delivery sites registered for this party.
     *
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PartyType::class,
        ];
    }
}
