<?php

namespace App\Contracts\Fakes;

use App\Contracts\TollCatalog;

/**
 * Toll-catalog fake that has no prices until the P9 booth catalog lands.
 */
class FakeTollCatalog implements TollCatalog
{
    /**
     * Report no known price.
     */
    public function price(string $booth, string $axles, string $direction): ?int
    {
        return null;
    }
}
