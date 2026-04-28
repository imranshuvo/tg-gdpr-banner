<?php

namespace App\Services\Analytics;

use App\Models\Site;
use Carbon\CarbonImmutable;

/**
 * Builds the analytics payload for a Site over a given period.
 *
 * Single source of truth for the per-site stats shown to both admins
 * (Admin\SiteController::analytics) and customers (Customer\SiteController::analytics).
 *
 * Output is a flat array keyed for direct use in Blade. No DB writes.
 */
class SiteAnalyticsService
{
    public const ALLOWED_PERIODS = [7, 30, 90, 365];

    public function forSite(Site $site, int $period = 30): array
    {
        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 30;
        }

        $endDate           = now()->endOfDay();
        $startDate         = now()->subDays($period - 1)->startOfDay();
        $previousStartDate = $startDate->copy()->subDays($period);
        $previousEndDate   = $startDate->copy()->subSecond();

        $sessions = $site->sessions()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        $previousSessions = $site->sessions()
            ->whereBetween('date', [$previousStartDate->toDateString(), $previousEndDate->toDateString()])
            ->get();

        $usage = $site->usage()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        $totalSessions     = (int) $sessions->sum('total_sessions');
        $bannerShown       = (int) $sessions->sum('banner_shown');
        $acceptAllCount    = (int) $sessions->sum('consent_given');
        $rejectAllCount    = (int) $sessions->sum('consent_denied');
        $customCount       = (int) $sessions->sum('consent_customized');
        $totalConsents     = $acceptAllCount + $rejectAllCount + $customCount;
        $noInteractionCount = max((int) $sessions->sum('no_action'), max(0, $bannerShown - $totalConsents));

        $consentRate   = $bannerShown > 0   ? round(($totalConsents / $bannerShown) * 100, 1) : null;
        $acceptAllRate = $totalConsents > 0 ? round(($acceptAllCount / $totalConsents) * 100, 1) : 0.0;

        $previousTotalSessions = (int) $previousSessions->sum('total_sessions');
        $sessionChange         = $this->calculateTrendPercentage($totalSessions, $previousTotalSessions);

        $currentMonthSessions = $usage?->total_sessions ?? $site->getCurrentMonthSessions();
        $sessionLimit         = $usage?->session_limit  ?? $site->getSessionLimit();

        return [
            'total_sessions'      => $totalSessions,
            'session_change'      => $sessionChange,
            'total_consents'      => $totalConsents,
            'consent_rate'        => $consentRate,
            'has_banner_data'     => $bannerShown > 0,
            'accept_all_count'    => $acceptAllCount,
            'accept_all_rate'     => $acceptAllRate,
            'reject_all_count'    => $rejectAllCount,
            'custom_count'        => $customCount,
            'no_interaction_count' => $noInteractionCount,
            'sessions_used'       => $currentMonthSessions,
            'sessions_limit'      => $sessionLimit,
            'usage_percentage'    => $sessionLimit > 0
                ? round(min(100, ($currentMonthSessions / $sessionLimit) * 100), 1)
                : 100.0,
            'category_rates'      => $this->buildCategoryRates($sessions, $totalConsents),
            'sessions_labels'     => $this->buildSessionLabels($sessions, $startDate, $endDate),
            'sessions_data'       => $this->buildSessionData($sessions, $startDate, $endDate),
            'gcm_stats'           => $this->buildGcmStats($site, $startDate),
            'top_countries'       => $this->aggregateBreakdown($sessions, 'geo_breakdown'),
            'device_breakdown'    => $this->aggregateBreakdown($sessions, 'device_breakdown'),
        ];
    }

    public function recentConsents(Site $site, int $period = 30, int $take = 10)
    {
        $startDate = now()->subDays($period - 1)->startOfDay();

        return $site->consentRecords()
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->take($take)
            ->get();
    }

    private function calculateTrendPercentage(int $current, int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function buildCategoryRates($sessions, int $totalConsents): array
    {
        if ($totalConsents <= 0) {
            return [
                'necessary'  => 100.0,
                'functional' => 0.0,
                'analytics'  => 0.0,
                'marketing'  => 0.0,
            ];
        }

        return [
            'necessary'  => 100.0,
            'functional' => round(($sessions->sum('accepted_functional') / $totalConsents) * 100, 1),
            'analytics'  => round(($sessions->sum('accepted_analytics')  / $totalConsents) * 100, 1),
            'marketing'  => round(($sessions->sum('accepted_marketing')  / $totalConsents) * 100, 1),
        ];
    }

    private function buildSessionLabels($sessions, $startDate, $endDate): array
    {
        $labels = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $labels[] = $date->format('M j');
        }
        return $labels;
    }

    private function buildSessionData($sessions, $startDate, $endDate): array
    {
        $sessionsByDate = $sessions->keyBy(fn ($s) => $s->date->toDateString());
        $data = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $data[] = (int) ($sessionsByDate[$date->toDateString()]->total_sessions ?? 0);
        }
        return $data;
    }

    private function buildGcmStats(Site $site, $startDate): array
    {
        $keys  = ['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization'];
        $stats = array_fill_keys($keys, ['granted' => 0, 'total' => 0]);

        $site->consentRecords()
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('gcm_state')
            ->select(['id', 'gcm_state'])
            ->orderBy('id')
            ->chunkById(500, function ($records) use (&$stats, $keys) {
                foreach ($records as $record) {
                    foreach ($keys as $key) {
                        if (! array_key_exists($key, $record->gcm_state ?? [])) {
                            continue;
                        }
                        $stats[$key]['total']++;
                        if (($record->gcm_state[$key] ?? null) === 'granted') {
                            $stats[$key]['granted']++;
                        }
                    }
                }
            });

        return $stats;
    }

    private function aggregateBreakdown($sessions, string $field): array
    {
        $totals = [];
        foreach ($sessions as $session) {
            foreach (($session->{$field} ?? []) as $key => $count) {
                $totals[$key] = ($totals[$key] ?? 0) + (int) $count;
            }
        }
        arsort($totals);
        return array_slice($totals, 0, 5, true);
    }
}
