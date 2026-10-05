<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\PackageStatus;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A trackable physical unit with a barcode/SSCC and a lifecycle.
 *
 * @property int $id
 * @property int $team_id
 * @property int $shipment_id
 * @property string $code
 * @property PackageStatus $status
 * @property int|null $weight_grams
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Shipment $shipment
 * @property-read Team $team
 */
#[Fillable(['shipment_id', 'code', 'status', 'weight_grams'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the shipment this package belongs to.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
        ];
    }
}
