<?php

namespace App\Services\Payments;

/**
 * Provider-agnostic checkout-session DTO returned by a PaymentProvider driver.
 */
final class CheckoutSession
{
    public function __construct(
        public readonly string $url,             // redirect target for the customer
        public readonly string $providerSessionId,
        public readonly string $provider,        // 'stripe' | 'frisbii'
        public readonly string $mode,            // 'test' | 'live'
    ) {}
}
