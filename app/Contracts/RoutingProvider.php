<?php

namespace App\Contracts;

use App\Contracts\Data\RouteEstimate;

/**
 * A maps/routing adapter.
 *
 * Optimization is advisory; the routing estimate only informs a dispatcher, it
 * never overrides a hard capacity or compliance gate.
 */
interface RoutingProvider
{
    /**
     * Estimate the route through the given points.
     *
     * @param  array<int, array{lat: float, lng: float}>  $points
     */
    public function estimate(array $points): RouteEstimate;
}
