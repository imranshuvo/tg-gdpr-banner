<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for both Stripe and Frisbii webhooks.
 *
 * Routes:
 *   POST /webhooks/payments/{provider}    where provider ∈ {stripe, frisbii}
 *
 * The route is unauthenticated (CSRF-exempt — see bootstrap/app.php). Trust comes
 * from the provider's signed payload, verified by the driver before any state
 * change. A failed signature returns 400; a successful one always returns 200
 * (even on internal errors) so the provider doesn't retry-storm us — internal
 * errors are logged and surface in observability.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(private PaymentManager $payments) {}

    public function __invoke(Request $request, string $provider): Response
    {
        try {
            $driver = $this->payments->get($provider);
        } catch (\InvalidArgumentException) {
            return response('unknown provider', 404);
        }

        if (! $driver->verifyWebhookSignature($request)) {
            Log::warning('Payment webhook signature rejected', [
                'provider'   => $provider,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response('invalid signature', 400);
        }

        try {
            $driver->handleWebhookEvent($request);
        } catch (\Throwable $e) {
            // Acknowledge to avoid retry storms; surface in logs.
            Log::error('Payment webhook handler failed', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
        }

        return response('', 200);
    }
}
