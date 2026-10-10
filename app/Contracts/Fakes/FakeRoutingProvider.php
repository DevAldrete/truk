<?php

namespace App\Contracts\Fakes;

use App\Contracts\Data\RouteEstimate;
use App\Contracts\RoutingProvider;

/**
 * Routing fake returning a zero estimate so optimization tests are deterministic.
 */
class FakeRoutingProvider implements RoutingProvider
{
    /**
     * Return a zero-distance, zero-duration estimate.
     *
     * @param  array<int, array{lat: float, lng: float}>  $points
     */
    public function estimate(array $points): RouteEstimate
    {
        return new RouteEstimate(0, 0);
    }
}
