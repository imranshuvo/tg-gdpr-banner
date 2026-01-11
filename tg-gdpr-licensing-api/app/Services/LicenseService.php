<?php

namespace App\Services;

use App\Models\License;
use App\Models\Activation;
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

    public function createLicense(int $customerId, string $plan): License
    {
        $maxActivations = match ($plan) {
            'single' => 1,
            '3-sites' => 3,
            '10-sites' => 10,
            default => 1,
        };

        return License::create([
            'customer_id' => $customerId,
            'license_key' => $this->generateLicenseKey(),
            'plan' => $plan,
            'max_activations' => $maxActivations,
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function activate(string $licenseKey, string $domain, string $siteUrl): array
    {
        $license = License::where('license_key', $licenseKey)->first();

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
