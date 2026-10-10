<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A versioned global reference catalog (SAT, SEPOMEX, toll booths).
 *
 * Global reference data is never tenant data: it is queried without the team
 * scope and is never mutated by a tenant request.
 *
 * @property int $id
 * @property string $name
 * @property string $version
 * @property Carbon|null $published_at
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'version', 'published_at', 'source'])]
class CatalogVersion extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'date',
        ];
    }
}
