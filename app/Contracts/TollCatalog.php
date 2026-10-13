<?php

namespace App\Contracts;

/**
 * A toll-booth price catalog adapter.
 *
 * Toll price depends on booth × axle configuration × direction, so it is a
 * reference lookup rather than a value stored on the expense.
 */
interface TollCatalog
{
    /**
     * Price in minor units for a booth, axle configuration, and direction.
     */
    public function price(string $booth, string $axles, string $direction): ?int;
}
