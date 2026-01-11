<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Services\Monitoring\AlertService;
use Illuminate\Console\Command;

class MonitorLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:monitor {--send-alerts : Send email alerts for issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor licenses for expiry, activation limits, and other issues';

    /**
     * Execute the console command.
     */
    public function handle(AlertService $alertService): int
    {
        $this->info('Starting license monitoring...');
        
        $sendAlerts = $this->option('send-alerts');
        
        // Check for expiring licenses (next 30 days)
        $expiringLicenses = License::where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->get();

        if ($expiringLicenses->count() > 0) {
            $this->warn("Found {$expiringLicenses->count()} licenses expiring in the next 30 days");
            
            foreach ($expiringLicenses as $license) {
                if ($sendAlerts) {
                    $alertService->licenseExpiring($license);
                }
                $this->line("  - {$license->license_key} expires on {$license->expires_at->format('Y-m-d')}");
            }
        }

        // Check for expired licenses
        $expiredLicenses = License::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredLicenses->count() > 0) {
            $this->error("Found {$expiredLicenses->count()} expired licenses");
            
            foreach ($expiredLicenses as $license) {
                if ($sendAlerts) {
                    $alertService->licenseExpired($license);
                }
                $this->line("  - {$license->license_key} expired on {$license->expires_at->format('Y-m-d')}");
            }
        }

        // Check for licenses at activation limit
        $allLicenses = License::with('activations')->get();
        $fullLicenses = $allLicenses->filter(function($license) {
            return $license->activations->count() >= $license->max_activations;
        });

        if ($fullLicenses->count() > 0) {
            $this->warn("Found {$fullLicenses->count()} licenses at activation limit");
            
            foreach ($fullLicenses as $license) {
                if ($sendAlerts) {
                    $alertService->licenseActivationLimitReached($license);
                }
                $this->line("  - {$license->license_key} ({$license->activations->count()}/{$license->max_activations})");
            }
        }

        // Check for inactive sites (no heartbeat in 7+ days)
        $staleActivations = \App\Models\Activation::where('last_checked_at', '<=', now()->subDays(7))->get();

        if ($staleActivations->count() > 0) {
            $this->warn("Found {$staleActivations->count()} activations with no heartbeat in 7+ days");
            
            foreach ($staleActivations->groupBy('license_id') as $licenseId => $activations) {
                $license = License::find($licenseId);
                if ($sendAlerts && $license) {
                    $alertService->warning(
                        'license',
                        'Stale License Activations',
                        "License {$license->license_key} has {$activations->count()} activations with no recent heartbeat",
                        [
                            'license_id' => $licenseId,
                            'stale_count' => $activations->count(),
                        ]
                    );
                }
            }
        }

        $this->info('License monitoring complete!');
        
        return Command::SUCCESS;
    }
}

