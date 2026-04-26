<?php

namespace App\Services\Payments\Providers;

use App\Models\Plan;
use App\Models\User;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\Contracts\PaymentProvider;
use App\Services\Payments\PaymentSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use RuntimeException;

/**
 * Frisbii driver.
 *
 * MVP scaffold: contract-conforming, reads admin-managed credentials, verifies
 * HMAC-SHA256 webhook signatures. Hosted-checkout + cancel are stubbed pending
 * endpoint mapping against a sandbox account — see TODO markers below. The rest
 * of the system (admin UI, settings, webhook routing, manager) treats this driver
 * as a first-class peer of Stripe so the wiring is in place the day creds arrive.
 *
 * Docs: https://docs.frisbii.com/reference/introduction
 */
class FrisbiiProvider implements PaymentProvider
{
    private PaymentSettings $settings;

    /** Default API base. Override per environment via the admin "API endpoint" field. */
    private const DEFAULT_BASE_URL = 'https://api.frisbii.com';

    public function __construct()
    {
        $this->settings = new PaymentSettings('frisbii');
    }

    public function name(): string  { return 'frisbii'; }
    public function label(): string { return 'Frisbii'; }

    public function isEnabled(): bool { return $this->settings->enabled(); }
    public function mode(): string    { return $this->settings->mode(); }

    public function isConfigured(): bool
    {
        return ! empty($this->settings->secretKey());
    }

    public function startCheckout(User $user, Plan $plan, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $this->ensureReady();

        $planId = $plan->providerPriceId('frisbii', $this->mode());
        if (! $planId) {
            throw new RuntimeException("Plan '{$plan->slug}' has no Frisbii plan ID for mode '{$this->mode()}'.");
        }

        // TODO(frisbii): map to the real endpoint once we have a sandbox key + sample request.
        // Likely shape (per Frisbii REST conventions): POST {base}/v1/checkout/sessions
        //   { customer: { email, name, vat_number? }, plan_id, success_url, cancel_url, mode }
        // Response: { id, url, status }
        throw new RuntimeException(
            'FrisbiiProvider::startCheckout is awaiting endpoint mapping. '
            . 'Provide a sandbox API key + a sample successful checkout response and this will land same-day.'
        );
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        $this->ensureReady();

        // TODO(frisbii): DELETE {base}/v1/subscriptions/{id} (or POST /cancel) — confirm.
        $this->client()->delete("/v1/subscriptions/{$providerSubscriptionId}")->throw();

        Subscription::where('provider', 'frisbii')
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->update(['stripe_status' => 'canceled', 'ends_at' => now()]);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = $this->settings->webhookSecret();
        // Frisbii's exact header name to be confirmed; common conventions are
        // X-Signature / X-Frisbii-Signature. We try both.
        $sig = $request->header('X-Frisbii-Signature') ?: $request->header('X-Signature');

        if (! $secret || ! $sig) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $sig);
    }

    public function handleWebhookEvent(Request $request): void
    {
        $event = $request->json()->all();
        $type  = $event['type'] ?? $event['event'] ?? null;
        $obj   = $event['data'] ?? $event['payload'] ?? [];

        // TODO(frisbii): confirm exact event type strings. The names below are the
        // generic billing-platform conventions — adjust once we see live payloads.
        match ($type) {
            'subscription.created', 'checkout.completed' => $this->onSubscriptionActivated($obj),
            'subscription.updated', 'subscription.renewed' => $this->onSubscriptionUpdated($obj),
            'subscription.canceled', 'subscription.deleted' => $this->onSubscriptionCanceled($obj),
            default => Log::info('Frisbii webhook ignored', ['type' => $type]),
        };
    }

    /** ─── private helpers ──────────────────────────────────────────────── */

    private function ensureReady(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Frisbii is not enabled by the super admin.');
        }
        if (! $this->isConfigured()) {
            throw new RuntimeException("Frisbii credentials missing for mode '{$this->mode()}'.");
        }
    }

    private function client(): PendingRequest
    {
        $base = $this->settings->endpoint() ?: self::DEFAULT_BASE_URL;

        return Http::baseUrl(rtrim($base, '/'))
            ->withToken($this->settings->secretKey())   // TODO(frisbii): swap for the real auth scheme
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function onSubscriptionActivated(array $obj): void
    {
        // TODO(frisbii): map response fields once we have live payload shape.
    }

    private function onSubscriptionUpdated(array $obj): void
    {
        // TODO(frisbii): same as above.
    }

    private function onSubscriptionCanceled(array $obj): void
    {
        $id = $obj['id'] ?? $obj['subscription_id'] ?? null;
        if (! $id) return;

        Subscription::where('provider', 'frisbii')
            ->where('provider_subscription_id', $id)
            ->update(['stripe_status' => 'canceled', 'ends_at' => now()]);
    }
}
