<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\LoadStatus;
use Database\Factories\LoadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A group of shipments planned together.
 *
 * @property int $id
 * @property int $team_id
 * @property string $number
 * @property LoadStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Shipment> $shipments
 * @property-read Team $team
 */
#[Fillable(['number', 'status', 'notes'])]
class Load extends Model
{
    /** @use HasFactory<LoadFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the shipments grouped into this load.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LoadStatus::class,
        ];
    }
}
