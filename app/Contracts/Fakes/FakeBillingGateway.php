<?php

namespace App\Contracts\Fakes;

use App\Contracts\BillingGateway;

/**
 * SaaS billing fake for the test suite.
 */
class FakeBillingGateway implements BillingGateway
{
    /**
     * Return a synthetic subscription id.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function subscribe(string $tenant, string $plan, array $metadata = []): string
    {
        return 'fake-subscription-'.$plan;
    }
}
