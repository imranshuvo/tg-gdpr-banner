<?php

namespace App\Services;

use App\Models\License;
use App\Models\Activation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseService
{
    public function generateLicenseKey(): string
    {
        return strtoupper(sprintf(
            '%s-%s-%s-%s',
            Str::random(4),
            Str::random(4),
            Str::random(4),
            Str::random(4)
        ));
    }

    /**
     * Manually create a License (admin-side path: superadmin grants a license
     * outside the payment flow, e.g. for partners/comp accounts).
     *
     * Reads max_activations from the Plan record so the slug, plan_id, and
     * activation cap stay aligned with the seeded plans table — superseding
     * the legacy hardcoded match on 'single'/'3-sites'/'10-sites'.
     */
    public function createLicense(int $customerId, Plan $plan, ?\DateTimeInterface $expiresAt = null): License
    {
        return License::create([
            'customer_id'     => $customerId,
            'plan'            => $plan->slug,
            'plan_id'         => $plan->id,
            'license_key'     => $this->generateLicenseKey(),
            'max_activations' => $plan->max_sites,
            'expires_at'      => $expiresAt ?? now()->addYear(),
            'status'          => 'active',
        ]);
    }

    /**
     * Provision a License from a successful checkout.
     *
     * Called from payment webhooks (Stripe / Frisbii). Idempotent: re-delivery
     * of the same provider event must NOT create a duplicate License — return
     * the existing one. The provider_subscription_id column has a unique index
     * to enforce this at the DB level too.
     *
     * Reads max_activations from the Plan record (single source of truth)
     * rather than the legacy hardcoded match in createLicense().
     */
    public function provisionForCheckout(User $user, Plan $plan, string $providerSubscriptionId): License
    {
        if (! $user->customer_id) {
            throw new \RuntimeException("User {$user->id} has no customer_id; cannot provision a License.");
        }

        return DB::transaction(function () use ($user, $plan, $providerSubscriptionId) {
            $existing = License::where('provider_subscription_id', $providerSubscriptionId)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            return License::create([
                'customer_id'              => $user->customer_id,
                'plan'                     => $plan->slug,
                'plan_id'                  => $plan->id,
                'provider_subscription_id' => $providerSubscriptionId,
                'license_key'              => $this->generateLicenseKey(),
                'max_activations'          => $plan->max_sites,
                'expires_at'               => now()->addYear(),
                'status'                   => 'active',
            ]);
        });
    }

    public function activate(string $licenseKey, string $domain, string $siteUrl): array
    {
        // Serialise concurrent activations of the same license: lockForUpdate holds
        // a row lock on the license until the transaction commits, so canActivate()
        // and Activation::create() run as one atomic step. Two simultaneous requests
        // for different domains on a 1-site license can no longer both succeed.
        return DB::transaction(function () use ($licenseKey, $domain, $siteUrl) {
            $license = License::where('license_key', $licenseKey)
                ->lockForUpdate()
                ->first();

            if (!$license) {
                return ['success' => false, 'message' => 'Invalid license key'];
            }

            if ($license->status !== 'active') {
                return ['success' => false, 'message' => 'License is not active'];
            }

            if ($license->isExpired()) {
                $license->update(['status' => 'expired']);
                return ['success' => false, 'message' => 'License has expired'];
            }

            $existingActivation = $license->activations()
                ->where('domain', $domain)
                ->first();

            if ($existingActivation) {
                $existingActivation->update([
                    'last_check_at' => now(),
                    'status' => 'active',
                ]);

                return [
                    'success' => true,
                    'message' => 'License reactivated successfully',
                    'data' => [
                        'license_key' => $license->license_key,
                        'plan' => $license->plan,
                        'expires_at' => $license->expires_at->toIso8601String(),
                    ],
                ];
            }

            if (!$license->canActivate()) {
                return [
                    'success' => false,
                    'message' => 'Maximum activations reached for this license',
                ];
            }

            Activation::create([
                'license_id' => $license->id,
                'domain' => $domain,
                'site_url' => $siteUrl,
                'last_check_at' => now(),
                'status' => 'active',
            ]);

            return [
                'success' => true,
                'message' => 'License activated successfully',
                'data' => [
                    'license_key' => $license->license_key,
                    'plan' => $license->plan,
                    'expires_at' => $license->expires_at->toIso8601String(),
                ],
            ];
        });
    }

    public function deactivate(string $licenseKey, string $domain): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return ['success' => false, 'message' => 'Invalid license key'];
        }

        $activation = $license->activations()
            ->where('domain', $domain)
            ->first();

        if (!$activation) {
            return ['success' => false, 'message' => 'No activation found for this domain'];
        }

        $activation->update(['status' => 'inactive']);

        return [
            'success' => true,
            'message' => 'License deactivated successfully',
        ];
    }

    public function verify(string $licenseKey, string $domain): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return ['success' => false, 'message' => 'Invalid license key'];
        }

        $activation = $license->activations()
            ->where('domain', $domain)
            ->where('status', 'active')
            ->first();

        if (!$activation) {
            return ['success' => false, 'message' => 'License not activated for this domain'];
        }

        if ($license->isExpired()) {
            $license->update(['status' => 'expired']);
            return ['success' => false, 'message' => 'License has expired'];
        }

        $activation->update(['last_check_at' => now()]);

        return [
            'success' => true,
            'message' => 'License is valid',
            'data' => [
                'license_key' => $license->license_key,
                'plan' => $license->plan,
                'expires_at' => $license->expires_at->toIso8601String(),
                'status' => $license->status,
            ],
        ];
    }
}
