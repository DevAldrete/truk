<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A person who operates a vehicle, either on the own fleet or for a carrier.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $user_id
 * @property int|null $carrier_party_id
 * @property string $name
 * @property string $phone
 * @property string|null $license_number
 * @property Carbon|null $license_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ComplianceDocument> $documents
 * @property-read Party|null $carrierParty
 * @property-read User|null $user
 * @property-read Team $team
 */
#[Fillable(['user_id', 'carrier_party_id', 'name', 'phone', 'license_number', 'license_expires_at'])]
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
     * Get the login linked to this driver, when any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the compliance documents attached to this driver.
     *
     * @return MorphMany<ComplianceDocument, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    /**
     * Get the incidents reported against this driver.
     *
     * @return HasMany<Incident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
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
