<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Payments\PaymentManager::class);
    }

    public function boot(): void
    {
        $this->configureRateLimits();
    }

    /**
     * Public-API rate limits.
     *
     * Sized to never throttle legitimate plugin traffic but stop brute-force
     * enumeration and abuse. Keys prefer the license_key / site_token (so that
     * sites behind shared NAT / managed-WP hosts aren't bucketed together) and
     * fall back to IP for unauthenticated paths.
     */
    private function configureRateLimits(): void
    {
        // Activate / deactivate are once-per-site-lifetime. 20/min covers retries.
        RateLimiter::for('license-mutations', function (Request $request) {
            $key = $request->input('license_key') ?: $request->ip();
            return Limit::perMinute(20)->by("license-mut:{$key}");
        });

        // Verify is the WP plugin's daily heartbeat — 60/min is ~1/sec headroom.
        RateLimiter::for('license-verify', function (Request $request) {
            $key = $request->input('license_key') ?: $request->ip();
            return Limit::perMinute(60)->by("license-verify:{$key}");
        });

        // /site/* and /consents/* /sessions/* /cookies/* — high-volume legit traffic.
        // Rate is per IP, not per site_token, so a high-traffic site isn't throttled
        // (each visitor has a unique IP). 600/min per IP stops blatant scripted abuse.
        RateLimiter::for('site-public', function (Request $request) {
            return Limit::perMinute(600)->by("site-pub:{$request->ip()}");
        });

        // DSAR is visitor-facing — strict per-IP to deter spam submissions.
        RateLimiter::for('dsar-public', function (Request $request) {
            return Limit::perHour(30)->by("dsar-pub:{$request->ip()}");
        });
    }
}
