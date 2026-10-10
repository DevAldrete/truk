<?php

namespace App\Contracts;

use Carbon\CarbonInterface;

/**
 * A hardware-agnostic telematics/GPS adapter.
 *
 * Vendors are fragmented, so positions are normalised to a common shape before
 * they reach the tracking domain.
 */
interface TelematicsProvider
{
    /**
     * Get the positions reported for a device since a moment.
     *
     * @return array<int, array{lat: float, lng: float, speed: float|null, recorded_at: string, source: string}>
     */
    public function positionsSince(string $deviceId, CarbonInterface $since): array;
}
