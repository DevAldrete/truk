<?php

namespace App\Contracts;

/**
 * A SaaS subscription billing adapter.
 *
 * Kept separate from operational invoicing: this is how the platform bills a
 * tenant, not how the tenant bills its customers.
 */
interface BillingGateway
{
    /**
     * Subscribe a tenant to a plan and return the gateway's subscription id.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function subscribe(string $tenant, string $plan, array $metadata = []): string;
}
