<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A toll booth, versioned; per-axle prices attach in the integrations phase.
 *
 * @property int $id
 * @property string $name
 * @property string|null $highway
 * @property string|null $state
 * @property string|null $direction
 * @property string $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'highway', 'state', 'direction', 'version'])]
class TollBooth extends Model {}
