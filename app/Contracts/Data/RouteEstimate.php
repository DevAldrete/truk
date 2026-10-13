<?php

namespace App\Contracts\Data;

/**
 * A routing estimate in base units (metres and seconds).
 */
readonly class RouteEstimate
{
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
    ) {}
}
