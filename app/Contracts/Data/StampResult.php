<?php

namespace App\Contracts\Data;

use App\Enums\DocumentStatus;
use Carbon\CarbonInterface;

/**
 * The result of a PAC stamping call.
 */
readonly class StampResult
{
    public function __construct(
        public string $uuid,
        public DocumentStatus $status,
        public ?string $xml = null,
        public ?string $pdf = null,
        public ?CarbonInterface $stampedAt = null,
    ) {}
}
