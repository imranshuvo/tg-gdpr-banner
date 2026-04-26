<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentProvider;
use App\Services\Payments\Providers\FrisbiiProvider;
use App\Services\Payments\Providers\StripeProvider;
use InvalidArgumentException;

/**
 * Resolves payment-provider drivers and answers "which providers can a customer
 * actually pay with right now?" — the answer drives both the customer checkout
 * UI and the super-admin status dashboard.
 */
class PaymentManager
{
    /** @var array<string, PaymentProvider> */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'stripe'  => new StripeProvider(),
            'frisbii' => new FrisbiiProvider(),
        ];
    }

    /** @return array<string, PaymentProvider> All registered drivers, regardless of state. */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $name): PaymentProvider
    {
        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException("Unknown payment provider: {$name}");
        }
        return $this->providers[$name];
    }

    /** @return array<string, PaymentProvider> Drivers the super admin has toggled on AND configured. */
    public function active(): array
    {
        return array_filter(
            $this->providers,
            fn (PaymentProvider $p) => $p->isEnabled() && $p->isConfigured(),
        );
    }

    /** Single-provider shortcut: returns the only active driver, or null if 0 / >1 are active. */
    public function default(): ?PaymentProvider
    {
        $active = $this->active();
        return count($active) === 1 ? array_values($active)[0] : null;
    }
}
