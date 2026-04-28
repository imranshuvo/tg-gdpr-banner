<?php

namespace App\Services\Compliance;

use App\Models\ConsentRecord;
use App\Models\DsarRequest;
use App\Models\Site;
use App\Services\Analytics\SiteAnalyticsService;
use App\Services\Consent\ConsentSigner;

/**
 * Builds the per-site GDPR compliance report payload.
 *
 * The output answers what an auditor or DPA inspector would ask:
 *   - How many consents in the period? Acceptance breakdown? Per-category?
 *   - Are consent records tamper-evident? (HMAC signature spot-check)
 *   - What cookies were detected on the site at last scan?
 *   - DSAR log: requests received, fulfilled, SLA breached?
 *   - Retention: how many records were purged for being past expires_at?
 *   - Banner config snapshot at report-generation time.
 *   - Sub-processors used.
 *
 * Output is array-shaped for direct rendering in Blade or JSON export.
 *
 * Single source of truth across customer + admin GDPR-report controllers.
 */
class GdprReportService
{
    public const ALLOWED_PERIODS = [30, 90, 365];

    public function __construct(private SiteAnalyticsService $analytics) {}

    public function forSite(Site $site, int $period = 90): array
    {
        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 90;
        }

        $startDate = now()->subDays($period - 1)->startOfDay();
        $endDate   = now()->endOfDay();

        $analytics = $this->analytics->forSite($site, $period);

        return [
            'site' => [
                'id'             => $site->id,
                'domain'         => $site->domain,
                'site_name'      => $site->site_name,
                'site_url'       => $site->site_url,
                'created_at'     => $site->created_at?->toIso8601String(),
                'policy_version' => $site->policy_version,
                'status'         => $site->status,
            ],
            'period' => [
                'from' => $startDate->toIso8601String(),
                'to'   => $endDate->toIso8601String(),
                'days' => $period,
            ],
            'summary' => [
                'sessions'         => $analytics['total_sessions'],
                'banner_shown'     => $analytics['has_banner_data'] ? null : 0, // analytics has aggregate, not raw count
                'total_consents'   => $analytics['total_consents'],
                'consent_rate_pct' => $analytics['consent_rate'],
                'accept_all_pct'   => $analytics['accept_all_rate'],
            ],
            'consents' => [
                'by_method' => [
                    'accept_all'  => $analytics['accept_all_count'],
                    'reject_all'  => $analytics['reject_all_count'],
                    'customize'   => $analytics['custom_count'],
                    'no_action'   => $analytics['no_interaction_count'],
                ],
                'by_category_acceptance_pct' => $analytics['category_rates'],
                'gcm_v2_signals'             => $analytics['gcm_stats'],
                'top_countries'              => $analytics['top_countries'],
            ],
            'tamper_evidence' => $this->verifyTamperEvidence($site, $startDate),
            'cookies' => [
                'detected_count' => $site->cookies()->count(),
                'by_category'    => $this->cookiesByCategory($site),
                'last_scan_at'   => optional($this->latestScanReport())['scanned_at']
                    ? \Carbon\Carbon::createFromTimestamp($this->latestScanReport()['scanned_at'])->toIso8601String()
                    : null,
            ],
            'dsar' => $this->dsarStats($site, $startDate),
            'retention' => $this->retentionStats($site, $startDate),
            'banner_config' => $this->bannerConfigSnapshot($site),
            'sub_processors' => $this->subProcessors(),
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generator'    => config('app.name') . ' Compliance Report v1',
                'period_days'  => $period,
            ],
        ];
    }

    /**
     * Spot-check N most-recent consent records: how many have valid HMAC signatures?
     * "Tamper evidence" = "we can prove these records are unchanged since they were
     * written" → required for Article 7 ("consent must be demonstrable").
     */
    private function verifyTamperEvidence(Site $site, $startDate): array
    {
        $sample = $site->consentRecords()
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->take(100)
            ->get();

        $total       = $sample->count();
        $signed      = $sample->whereNotNull('signature')->count();
        $verified    = $sample->filter(fn (ConsentRecord $r) => $r->isSignatureValid())->count();
        $unsigned    = $total - $signed;
        $tampered    = $signed - $verified;

        return [
            'sample_size'                => $total,
            'records_signed'             => $signed,
            'records_unsigned_legacy'    => $unsigned,
            'signatures_verified'        => $verified,
            'signatures_failed_verify'   => $tampered,
            'integrity_status'           => $tampered === 0 ? 'pass' : 'FAIL — signature mismatch detected',
            'algorithm'                  => 'HMAC-SHA256 over canonical JSON payload, keyed with APP_KEY',
        ];
    }

    private function cookiesByCategory(Site $site): array
    {
        return $site->cookies()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    private function dsarStats(Site $site, $startDate): array
    {
        $base = DsarRequest::where('site_id', $site->id)->where('created_at', '>=', $startDate);

        $total      = (clone $base)->count();
        $completed  = (clone $base)->where('status', 'completed')->count();
        $rejected   = (clone $base)->where('status', 'rejected')->count();
        $pending    = (clone $base)->whereNotIn('status', ['completed', 'rejected'])->count();
        $slaBreaches = (clone $base)->where('sla_breached', true)->count();

        $avgResponseDays = (clone $base)
            ->whereNotNull('completed_at')
            ->get()
            ->avg(fn (DsarRequest $r) => $r->created_at->diffInDays($r->completed_at, true));

        return [
            'total_requests'         => $total,
            'completed'              => $completed,
            'rejected'               => $rejected,
            'pending'                => $pending,
            'sla_breaches'           => $slaBreaches,
            'avg_response_days'      => $avgResponseDays !== null ? round($avgResponseDays, 1) : null,
            'sla_target_days'        => 30, // GDPR Art. 12 default
            'by_request_type'        => (clone $base)
                ->selectRaw('request_type, COUNT(*) as count')
                ->groupBy('request_type')
                ->pluck('count', 'request_type')
                ->toArray(),
        ];
    }

    private function retentionStats(Site $site, $startDate): array
    {
        $expiryDays = $site->settings->consent_expiry_days ?? 365;

        $periodTotal   = $site->consentRecords()->where('created_at', '>=', $startDate)->count();
        $expiredAlive  = $site->consentRecords()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'consent_expiry_days'                  => $expiryDays,
            'consent_records_in_period'            => $periodTotal,
            'records_past_expiry_pending_purge'    => $expiredAlive,
            'auto_purge_schedule'                  => 'Daily at 03:00 UTC (consents:purge-expired)',
            'privacy_policy_claim'                 => "Consent records auto-deleted {$expiryDays} days after collection.",
        ];
    }

    private function bannerConfigSnapshot(Site $site): array
    {
        $s = $site->settings;

        return [
            'banner_position'          => $s->banner_position ?? 'bottom',
            'banner_layout'            => $s->banner_layout ?? 'bar',
            'show_reject_all'          => (bool) ($s->show_reject_all ?? true),
            'auto_block_scripts'       => (bool) ($s->auto_block_scripts ?? true),
            'log_consents'             => (bool) ($s->log_consents ?? true),
            'respect_dnt'              => (bool) ($s->respect_dnt ?? false),
            'gcm_v2_enabled'           => (bool) $site->gcm_enabled,
            'gcm_wait_for_update'      => (bool) ($s->gcm_wait_for_update ?? true),
            'tcf_22_enabled'           => (bool) $site->tcf_enabled,
            'geo_targeting_mode'       => $site->getGeoTargetingMode(),
            'geo_countries'            => $site->geo_countries ?? [],
            'policy_version'           => $site->policy_version,
            'policy_last_updated'      => $site->policy_updated_at?->toIso8601String(),
            'gcm_default_state'        => $s->gcm_default_state ?? null,
            'category_labels'          => $s->category_labels ?? null,
        ];
    }

    private function subProcessors(): array
    {
        return [
            [
                'name'    => 'Stripe Inc.',
                'role'    => 'Payment processing',
                'data'    => 'Customer billing data, payment method metadata',
                'region'  => 'United States',
                'sccs'    => 'Standard Contractual Clauses (Module 4) — see https://stripe.com/legal/dpa',
            ],
            [
                'name'    => '(Mail provider — configure via /admin/settings/mail)',
                'role'    => 'Transactional email delivery',
                'data'    => 'Customer email addresses, transactional message content',
                'region'  => 'Per provider DPA',
                'sccs'    => 'Per provider DPA',
            ],
            [
                'name'    => '(Hosting provider — your cPanel host)',
                'role'    => 'Application hosting + database',
                'data'    => 'All application data',
                'region'  => 'Per host',
                'sccs'    => 'Per host DPA',
            ],
        ];
    }

    private function latestScanReport(): array
    {
        // The cookie scanner persists its last report in a WP-side option, but
        // for the API-side report we read the Site::last_scan_at + cookies()
        // table. This stub is a placeholder until we sync scan reports server-
        // side too.
        return [];
    }
}
