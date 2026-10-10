<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\CfdiType;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The immutable fiscal snapshot of a trip.
 *
 * A stamped document is never overwritten; a correction creates a new row, and
 * the payload is frozen so a later edit to the trip cannot invalidate it.
 *
 * @property int $id
 * @property int $team_id
 * @property int $trip_id
 * @property CfdiType $type
 * @property string $schema_version
 * @property DocumentStatus $status
 * @property string|null $provider
 * @property string|null $cfdi_uuid
 * @property array<string, mixed> $payload
 * @property string|null $xml_path
 * @property string|null $pdf_path
 * @property Carbon|null $stamped_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Trip $trip
 * @property-read Team $team
 */
#[Fillable([
    'trip_id',
    'type',
    'schema_version',
    'status',
    'provider',
    'cfdi_uuid',
    'payload',
    'xml_path',
    'pdf_path',
    'stamped_at',
    'cancelled_at',
    'cancellation_reason',
])]
class TripCompliance extends Model
{
    use BelongsToTeam;

    /**
     * Get the trip this snapshot belongs to.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CfdiType::class,
            'status' => DocumentStatus::class,
            'payload' => 'array',
            'stamped_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
