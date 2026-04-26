<?php

namespace App\Services\Payments\Contracts;

use App\Models\Plan;
use App\Models\User;
use App\Services\Payments\CheckoutSession;
use Illuminate\Http\Request;

/**
 * Contract for a payment provider driver (Stripe, Frisbii, …).
 *
 * Each driver reads its credentials from the encrypted SystemSetting store
 * (group=payment, prefixed by name()), so super admins manage everything from
 * the admin UI — no env-var redeploy required.
 */
interface PaymentProvider
{
    /** Stable machine name. */
    public function name(): string;

    /** Human label for the admin UI. */
    public function label(): string;

    /** Credentials present for the active mode? */
    public function isConfigured(): bool;

    /** Has the super admin enabled this provider? */
    public function isEnabled(): bool;

    /** 'test' | 'live' — drives which key set is active. */
    public function mode(): string;

    /**
     * Start a hosted checkout for the given user + plan and return the
     * redirect URL the customer should be sent to.
     */
    public function startCheckout(User $user, Plan $plan, string $successUrl, string $cancelUrl): CheckoutSession;

    /** Cancel an active subscription at the provider. */
    public function cancelSubscription(string $providerSubscriptionId): void;

    /** True if the incoming webhook request's signature is valid. */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Translate a verified webhook event into an internal subscription state
     * change. Idempotent: re-delivery of the same event must be a no-op.
     */
    public function handleWebhookEvent(Request $request): void;
}
