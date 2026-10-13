<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A SEPOMEX / SAT postal-code record, versioned.
 *
 * @property int $id
 * @property string $postal_code
 * @property string $state
 * @property string $municipality
 * @property string|null $locality
 * @property string $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['postal_code', 'state', 'municipality', 'locality', 'version'])]
class SatPostalCode extends Model {}
