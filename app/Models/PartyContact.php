<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\PartyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A person to contact at a party.
 *
 * @property int $id
 * @property int $team_id
 * @property int $party_id
 * @property string $name
 * @property string|null $position
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Party $party
 * @property-read Team $team
 */
#[Fillable(['party_id', 'name', 'position', 'email', 'phone'])]
class PartyContact extends Model
{
    /** @use HasFactory<PartyContactFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the party this contact belongs to.
     *
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
