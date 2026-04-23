<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\ConsentRecord;
use App\Models\SiteSession;
use App\Models\SiteUsage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConsentController extends Controller
{
    /**
     * Get site settings for the client integration
     * Called during integration bootstrap to fetch latest settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        // Check if site is active
        if (!in_array($site->status, ['active', 'trial'])) {
            return response()->json([
                'success' => false,
                'message' => 'Site is not active',
                'status' => $site->status,
            ], 403);
        }
        
        // Check trial expiry
        if ($site->isTrialExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Trial period has expired',
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $site->getSettingsForPlugin(),
        ]);
    }

    /**
     * Sync consent records from the client integration
     * Called periodically (every 5 minutes) with a batch of consent records
     */
    public function syncConsents(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'consents' => 'required|array|max:1000',
            'consents.*.consent_id' => 'required|uuid',
            'consents.*.visitor_hash' => 'required|string|size:64',
            'consents.*.ip_anonymized' => 'nullable|string|max:45',
            'consents.*.country_code' => 'nullable|string|size:2',
            'consents.*.consent_categories' => 'required|array',
            'consents.*.consent_method' => 'required|in:accept_all,reject_all,customize,implicit',
            'consents.*.tcf_string' => 'nullable|string',
            'consents.*.gcm_state' => 'nullable|array',
            'consents.*.policy_version' => 'required|integer',
            'consents.*.device_type' => 'nullable|string',
            'consents.*.browser' => 'nullable|string',
            'consents.*.created_at' => 'required|date',
        ]);
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($validated['consents'] as $consentData) {
            try {
                ConsentRecord::updateOrCreate(
                    ['consent_id' => $consentData['consent_id']],
                    array_merge($consentData, [
                        'site_id' => $site->id,
                        'synced_from_plugin' => true,
                        'plugin_created_at' => $consentData['created_at'],
                    ])
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Synced {$imported} consent records",
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Record a single consent (real-time optional)
     */
    public function recordConsent(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'consent_id' => 'required|uuid',
            'visitor_hash' => 'required|string|size:64',
            'ip_anonymized' => 'nullable|string|max:45',
            'country_code' => 'nullable|string|size:2',
            'consent_categories' => 'required|array',
            'consent_method' => 'required|in:accept_all,reject_all,customize,implicit',
            'tcf_string' => 'nullable|string',
            'tcf_purposes' => 'nullable|array',
            'tcf_vendors' => 'nullable|array',
            'gcm_state' => 'nullable|array',
            'policy_version' => 'required|integer',
            'user_agent_hash' => 'nullable|string|size:64',
            'device_type' => 'nullable|string',
            'browser' => 'nullable|string',
        ]);
        
        $record = ConsentRecord::create(array_merge($validated, [
            'site_id' => $site->id,
            'expires_at' => now()->addDays($site->settings->consent_expiry_days ?? 365),
        ]));
        
        // Update session stats
        $session = SiteSession::getOrCreateForToday($site->id);
        $session->recordConsent($validated['consent_method'], $validated['consent_categories']);
        
        if (!empty($validated['country_code'])) {
            $session->recordGeo($validated['country_code']);
        }
        
        if (!empty($validated['device_type'])) {
            $session->recordDevice($validated['device_type']);
        }
        
        return response()->json([
            'success' => true,
            'consent_id' => $record->consent_id,
        ]);
    }

    /**
     * Sync session statistics from the client integration
     */
    public function syncSessions(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'sessions' => 'required|array',
            'sessions.*.date' => 'required|date',
            'sessions.*.total_sessions' => 'required|integer|min:0',
            'sessions.*.banner_shown' => 'required|integer|min:0',
            'sessions.*.consent_given' => 'required|integer|min:0',
            'sessions.*.consent_denied' => 'required|integer|min:0',
            'sessions.*.consent_customized' => 'required|integer|min:0',
            'sessions.*.no_action' => 'nullable|integer|min:0',
            'sessions.*.accepted_functional' => 'integer|min:0',
            'sessions.*.accepted_analytics' => 'integer|min:0',
            'sessions.*.accepted_marketing' => 'integer|min:0',
            'sessions.*.geo_breakdown' => 'nullable|array',
            'sessions.*.device_breakdown' => 'nullable|array',
        ]);
        
        foreach ($validated['sessions'] as $sessionData) {
            $session = SiteSession::query()
                ->where('site_id', $site->id)
                ->whereDate('date', $sessionData['date'])
                ->first();

            if (!$session) {
                $session = new SiteSession([
                    'site_id' => $site->id,
                    'date' => $sessionData['date'],
                ]);
            }

            $session->fill($this->mergeSessionPayload($session, $sessionData));
            $session->site_id = $site->id;
            $session->date = $sessionData['date'];
            $session->save();
        }
        
        // Update monthly usage
        $currentMonth = SiteUsage::getOrCreateForMonth($site);
        $totalSessions = $site->sessions()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('total_sessions');
        
        $currentMonth->update([
            'total_sessions' => $totalSessions,
            'total_consents' => $site->sessions()
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->selectRaw('SUM(consent_given + consent_denied + consent_customized) as total')
                ->value('total') ?? 0,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Sessions synced',
            'usage' => [
                'current_sessions' => $currentMonth->total_sessions,
                'session_limit' => $currentMonth->session_limit,
                'usage_percentage' => $currentMonth->getUsagePercentage(),
                'limit_exceeded' => $currentMonth->limit_exceeded,
            ],
        ]);
    }

    /**
     * Merge an incoming daily aggregate with the current stored session row.
     */
    private function mergeSessionPayload(SiteSession $existing, array $incoming): array
    {
        $counters = [
            'total_sessions',
            'banner_shown',
            'consent_given',
            'consent_denied',
            'consent_customized',
            'accepted_functional',
            'accepted_analytics',
            'accepted_marketing',
        ];

        $merged = [];

        foreach ($counters as $field) {
            $merged[$field] = max((int) ($existing->{$field} ?? 0), (int) ($incoming[$field] ?? 0));
        }

        $derivedNoAction = max(0, $merged['banner_shown'] - ($merged['consent_given'] + $merged['consent_denied'] + $merged['consent_customized']));
        $incomingNoAction = array_key_exists('no_action', $incoming) ? (int) $incoming['no_action'] : $derivedNoAction;

        $merged['no_action'] = max((int) ($existing->no_action ?? 0), $incomingNoAction, $derivedNoAction);
        $merged['geo_breakdown'] = $this->mergeSessionBreakdown($existing->geo_breakdown, $incoming['geo_breakdown'] ?? null);
        $merged['device_breakdown'] = $this->mergeSessionBreakdown($existing->device_breakdown, $incoming['device_breakdown'] ?? null);

        return $merged;
    }

    /**
     * Merge cumulative breakdown payloads without letting stale syncs overwrite newer counts.
     */
    private function mergeSessionBreakdown(?array $existing, ?array $incoming): array
    {
        $existing = is_array($existing) ? $existing : [];
        $incoming = is_array($incoming) ? $incoming : [];
        $merged = [];

        foreach (array_unique(array_merge(array_keys($existing), array_keys($incoming))) as $key) {
            $merged[$key] = max((int) ($existing[$key] ?? 0), (int) ($incoming[$key] ?? 0));
        }

        arsort($merged);

        return $merged;
    }

    /**
     * Get current usage stats
     */
    public function getUsage(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $usage = SiteUsage::getOrCreateForMonth($site);
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_sessions' => $usage->total_sessions,
                'session_limit' => $usage->session_limit,
                'usage_percentage' => $usage->getUsagePercentage(),
                'limit_exceeded' => $usage->limit_exceeded,
                'site_status' => $site->status,
                'trial_ends_at' => $site->trial_ends_at?->toIso8601String(),
                'days_remaining' => $site->isTrial() && $site->trial_ends_at 
                    ? max(0, now()->diffInDays($site->trial_ends_at, false)) 
                    : null,
            ],
        ]);
    }

    /**
     * Withdraw consent for a visitor
     */
    public function withdrawConsent(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'consent_id' => 'required|uuid',
            'reason' => 'nullable|string|max:500',
        ]);
        
        $record = ConsentRecord::where('site_id', $site->id)
            ->where('consent_id', $validated['consent_id'])
            ->first();
        
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Consent record not found',
            ], 404);
        }
        
        $record->withdraw($validated['reason'] ?? null);
        
        return response()->json([
            'success' => true,
            'message' => 'Consent withdrawn',
        ]);
    }

    /**
     * Validate site token from request header or body
     */
    private function validateSiteToken(Request $request): ?Site
    {
        $token = $request->header('X-Site-Token') 
            ?? $request->input('site_token');
        
        if (!$token) {
            return null;
        }
        
        return Site::where('site_token', $token)->first();
    }
}
