<?php

namespace App\Contracts\Fakes;

use App\Contracts\Data\StampResult;
use App\Contracts\PacProvider;
use App\Enums\DocumentStatus;
use Illuminate\Support\Str;

/**
 * Deterministic PAC fake for the test suite and local development.
 *
 * It stamps immediately and never calls a network; real providers are bound in
 * production once the fiscal phase lands.
 */
class FakePacProvider implements PacProvider
{
    /**
     * Stamp the payload and return a synthetic, stamped document.
     *
     * @param  array<string, mixed>  $payload
     */
    public function stamp(array $payload): StampResult
    {
        return new StampResult(
            uuid: (string) Str::uuid(),
            status: DocumentStatus::Stamped,
            xml: '<?xml version="1.0"?><cfdi:Comprobante/>',
            pdf: null,
            stampedAt: now(),
        );
    }

    /**
     * Cancel a stamped document.
     */
    public function cancel(string $uuid, string $reason): bool
    {
        return true;
    }

    /**
     * Report a synthetic document as cancelled.
     */
    public function status(string $uuid): DocumentStatus
    {
        return DocumentStatus::Cancelled;
    }
}
