<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A SAT unit-of-measure code (`c_ClaveUnidad`), versioned.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name', 'version'])]
class SatUnitCode extends Model {}
