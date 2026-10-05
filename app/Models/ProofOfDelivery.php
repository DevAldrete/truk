<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\ProofOfDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Evidence recorded for a delivery outcome: recipient, signature, photos, and
 * scanned documents.
 *
 * PODs are append-only. A correction creates a new record; the original is
 * never overwritten. Files live on the private `evidence` disk and are streamed
 * only through an authorized, tenant-scoped route.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $delivery_attempt_id
 * @property int|null $stop_id
 * @property int|null $shipment_id
 * @property string|null $recipient_name
 * @property string|null $signature_path
 * @property array<int, string>|null $photos
 * @property array<int, string>|null $document_paths
 * @property bool $consent
 * @property string|null $notes
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon $captured_at
 * @property string $idempotency_key
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DeliveryAttempt|null $attempt
 * @property-read Stop|null $stop
 * @property-read Shipment|null $shipment
 * @property-read User|null $recorder
 * @property-read Team $team
 */
#[Fillable([
    'delivery_attempt_id',
    'stop_id',
    'shipment_id',
    'recipient_name',
    'signature_path',
    'photos',
    'document_paths',
    'consent',
    'notes',
    'latitude',
    'longitude',
    'captured_at',
    'idempotency_key',
    'recorded_by',
])]
class ProofOfDelivery extends Model
{
    /** @use HasFactory<ProofOfDeliveryFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proofs_of_delivery';

    /**
     * Get the attempt this evidence belongs to, when any.
     *
     * @return BelongsTo<DeliveryAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DeliveryAttempt::class, 'delivery_attempt_id');
    }

    /**
     * Get the stop this evidence was captured at, when any.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the shipment this evidence concerns, when any.
     *
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the login that recorded this evidence.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'document_paths' => 'array',
            'consent' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'captured_at' => 'datetime',
        ];
    }
}
