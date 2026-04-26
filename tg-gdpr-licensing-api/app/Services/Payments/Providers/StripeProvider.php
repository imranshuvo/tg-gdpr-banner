<?php

namespace App\Services\Payments\Providers;

use App\Models\License;
use App\Models\Plan;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\Contracts\PaymentProvider;
use App\Services\Payments\PaymentSettings;
use Laravel\Cashier\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeProvider implements PaymentProvider
{
    private PaymentSettings $settings;

    public function __construct()
    {
        $this->settings = new PaymentSettings('stripe');
    }

    public function name(): string  { return 'stripe'; }
    public function label(): string { return 'Stripe'; }

    public function isEnabled(): bool { return $this->settings->enabled(); }
    public function mode(): string    { return $this->settings->mode(); }

    public function isConfigured(): bool
    {
        return ! empty($this->settings->secretKey()) && ! empty($this->settings->publicKey());
    }

    public function startCheckout(User $user, Plan $plan, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $this->ensureReady();

        $priceId = $plan->providerPriceId('stripe', $this->mode());
        if (! $priceId) {
            throw new UnexpectedValueException("Plan '{$plan->slug}' has no Stripe price ID for mode '{$this->mode()}'.");
        }

        // Cashier reads `services.stripe.secret`; we override at runtime so the
        // admin-managed key wins over any legacy env value.
        $this->bootCashierWithRuntimeCreds();

        $session = $user
            ->newSubscription("default-{$plan->slug}", $priceId)
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
                'metadata'    => [
                    'plan_slug' => $plan->slug,
                    'plan_id'   => (string) $plan->id,
                    'user_id'   => (string) $user->id,
                    'mode'      => $this->mode(),
                ],
            ]);

        return new CheckoutSession(
            url:               $session->url,
            providerSessionId: $session->id,
            provider:          'stripe',
            mode:              $this->mode(),
        );
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        $this->ensureReady();

        $sub = Subscription::where('provider', 'stripe')
            ->where('stripe_id', $providerSubscriptionId)
            ->firstOrFail();

        $sub->cancel();
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = $this->settings->webhookSecret();
        $sig    = $request->header('Stripe-Signature');
        if (! $secret || ! $sig) {
            return false;
        }

        try {
            Webhook::constructEvent($request->getContent(), $sig, $secret);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function handleWebhookEvent(Request $request): void
    {
        $event = $request->json()->all();
        $type  = $event['type'] ?? null;
        $obj   = $event['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed'  => $this->onCheckoutCompleted($obj),
            'customer.subscription.updated',
            'customer.subscription.created' => $this->onSubscriptionUpdated($obj),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($obj),
            default => null, // Cashier will handle the rest via its own webhook controller if mounted
        };
    }

    /** ─── private helpers ──────────────────────────────────────────────── */

    private function ensureReady(): void
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Stripe is not enabled by the super admin.');
        }
        if (! $this->isConfigured()) {
            throw new \RuntimeException("Stripe credentials missing for mode '{$this->mode()}'.");
        }
    }

    /**
     * Push the admin-managed Stripe secret/key into Cashier's runtime config.
     * This lets the admin UI control credentials without env-var redeploy.
     */
    private function bootCashierWithRuntimeCreds(): void
    {
        Config::set('cashier.key', $this->settings->publicKey());
        Config::set('cashier.secret', $this->settings->secretKey());
        Stripe::setApiKey($this->settings->secretKey());
    }

    private function onCheckoutCompleted(array $obj): void
    {
        $stripeSubId = $obj['subscription'] ?? null;
        $userId      = $obj['metadata']['user_id'] ?? null;
        $planId      = $obj['metadata']['plan_id'] ?? null;

        if (! $stripeSubId || ! $userId || ! $planId) {
            Log::warning('Stripe checkout.session.completed missing required metadata', [
                'has_subscription' => (bool) $stripeSubId,
                'has_user_id'      => (bool) $userId,
                'has_plan_id'      => (bool) $planId,
            ]);
            return;
        }

        $user = User::find($userId);
        $plan = Plan::find($planId);
        if (! $user || ! $plan || ! $user->customer_id) {
            Log::warning('Stripe checkout completed but user/plan/customer not resolvable', [
                'user_id'    => $userId,
                'plan_id'    => $planId,
                'stripe_sub' => $stripeSubId,
            ]);
            return;
        }

        // Provision the License the customer actually paid for. Idempotent —
        // re-delivery of the same event returns the existing License.
        $license = app(LicenseService::class)->provisionForCheckout($user, $plan, $stripeSubId);

        // Stamp our cross-provider columns on the Cashier Subscription row so
        // admin queries can group by (provider, mode) regardless of driver.
        Subscription::where('user_id', $userId)
            ->where('stripe_id', $stripeSubId)
            ->update([
                'provider'                 => 'stripe',
                'mode'                     => $obj['metadata']['mode'] ?? $this->mode(),
                'provider_subscription_id' => $stripeSubId,
                'plan_id'                  => $planId,
            ]);

        Log::info('License provisioned from Stripe checkout', [
            'license_id'   => $license->id,
            'subscription' => $stripeSubId,
            'user_id'      => $user->id,
            'plan'         => $plan->slug,
        ]);
    }

    private function onSubscriptionUpdated(array $obj): void
    {
        $stripeSubId = $obj['id'] ?? null;
        if (! $stripeSubId) return;

        Subscription::where('stripe_id', $stripeSubId)->update([
            'stripe_status' => $obj['status'] ?? null,
            'ends_at'       => isset($obj['cancel_at']) ? \Carbon\Carbon::createFromTimestamp($obj['cancel_at']) : null,
        ]);
    }

    private function onSubscriptionDeleted(array $obj): void
    {
        $stripeSubId = $obj['id'] ?? null;
        if (! $stripeSubId) return;

        Subscription::where('stripe_id', $stripeSubId)->update([
            'stripe_status' => 'canceled',
            'ends_at'       => now(),
        ]);
    }
}
