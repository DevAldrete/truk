<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\ComplianceDocumentType;
use Database\Factories\ComplianceDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A legality document attached to a driver, vehicle, or trailer.
 *
 * @property int $id
 * @property int $team_id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property ComplianceDocumentType $type
 * @property string|null $number
 * @property Carbon|null $issued_at
 * @property Carbon|null $expires_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Model $documentable
 * @property-read Team $team
 */
#[Fillable(['type', 'number', 'issued_at', 'expires_at', 'notes'])]
class ComplianceDocument extends Model
{
    /** @use HasFactory<ComplianceDocumentFactory> */
    use BelongsToTeam, HasFactory, SoftDeletes;

    /**
     * Get the driver, vehicle, or trailer this document belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the display payload for the document.
     *
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'number' => $this->number,
            'issued_at' => $this->issued_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'expired' => $this->expires_at?->isPast() ?? false,
            'expiring_soon' => $this->expires_at !== null
                && $this->expires_at->isFuture()
                && $this->expires_at->lessThanOrEqualTo(now()->addDays(30)),
            'notes' => $this->notes,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ComplianceDocumentType::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }
}
