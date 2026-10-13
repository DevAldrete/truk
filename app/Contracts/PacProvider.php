<?php

namespace App\Contracts;

use App\Contracts\Data\StampResult;
use App\Enums\DocumentStatus;

/**
 * A certified PAC (Proveedor Autorizado de Certificación) adapter.
 *
 * Domain code never talks to a vendor SDK; stamping and cancellation go through
 * this contract so the test suite can run against a fake and the provider can be
 * swapped without touching the domain.
 */
interface PacProvider
{
    /**
     * Stamp (timbrar) a prepared fiscal payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function stamp(array $payload): StampResult;

    /**
     * Cancel a previously stamped document, never deleting the original.
     */
    public function cancel(string $uuid, string $reason): bool;

    /**
     * Query the current status of a stamped document.
     */
    public function status(string $uuid): DocumentStatus;
}
