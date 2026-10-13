<?php

namespace App\Contracts\Fakes;

use App\Contracts\TelematicsProvider;
use Carbon\CarbonInterface;

/**
 * Telematics fake that reports no positions, so tracking tests are deterministic.
 */
class FakeTelematicsProvider implements TelematicsProvider
{
    /**
     * Report no positions.
     *
     * @return array<int, array{lat: float, lng: float, speed: float|null, recorded_at: string, source: string}>
     */
    public function positionsSince(string $deviceId, CarbonInterface $since): array
    {
        return [];
    }
}
