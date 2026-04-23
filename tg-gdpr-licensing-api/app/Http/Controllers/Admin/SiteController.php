<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteSettings;
use App\Models\Customer;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    /**
     * Display all sites across all customers (super admin view)
     */
    public function index(Request $request)
    {
        $query = Site::with(['customer', 'license', 'settings']);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                  ->orWhere('site_name', 'like', "%{$search}%")
                  ->orWhere('site_url', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);
        
        $sites = $query->paginate(20)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        
        return view('admin.sites.index', compact('sites', 'customers'));
    }

    /**
     * Show form to create a new site
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $licenses = License::where('status', 'active')->orderBy('license_key')->get();
        
        return view('admin.sites.create', compact('customers', 'licenses'));
    }

    /**
     * Store a new site
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'license_id' => 'nullable|exists:licenses,id',
            'domain' => 'required|string|max:255',
            'site_url' => 'required|url|max:500',
            'site_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,paused,trial,expired,deleted',
            'tcf_enabled' => 'boolean',
            'gcm_enabled' => 'boolean',
            'geo_targeting_enabled' => 'boolean',
            'geo_countries' => 'nullable|array',
        ]);
        
        // Extract domain from URL if not provided
        if (empty($validated['domain'])) {
            $validated['domain'] = parse_url($validated['site_url'], PHP_URL_HOST);
        }
        
        // Generate site token
        $validated['site_token'] = Str::random(64);
        
        // Set trial end date if trial
        if ($validated['status'] === 'trial') {
            $validated['trial_ends_at'] = now()->addDays(30);
        }
        
        $site = Site::create($validated);
        
        // Create default settings
        SiteSettings::create([
            'site_id' => $site->id,
        ]);
        
        return redirect()
            ->route('admin.sites.show', $site)
            ->with('success', 'Site created successfully.');
    }

    /**
     * Show site details with all settings
     */
    public function show(Site $site)
    {
        $site->load(['customer', 'license', 'settings', 'cookies']);
        
        // Get usage stats
        $currentMonthSessions = $site->getCurrentMonthSessions();
        $sessionLimit = $site->getSessionLimit();
        
        // Get recent consent stats
        $consentStats = $site->consentRecords()
            ->selectRaw('consent_method, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('consent_method')
            ->pluck('count', 'consent_method');
        
        return view('admin.sites.show', compact('site', 'currentMonthSessions', 'sessionLimit', 'consentStats'));
    }

    /**
     * Show form to edit site
     */
    public function edit(Site $site)
    {
        $site->load('settings');
        $customers = Customer::orderBy('name')->get();
        $licenses = License::where('status', 'active')
            ->orWhere('id', $site->license_id)
            ->orderBy('license_key')
            ->get();
        
        return view('admin.sites.edit', compact('site', 'customers', 'licenses'));
    }

    /**
     * Update site core settings
     */
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'license_id' => 'nullable|exists:licenses,id',
            'domain' => ['required', 'string', 'max:255', Rule::unique('sites')->ignore($site->id)->where('customer_id', $request->customer_id)],
            'site_url' => 'required|url|max:500',
            'site_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,paused,trial,expired,deleted',
            'tcf_enabled' => 'boolean',
            'gcm_enabled' => 'boolean',
            'geo_targeting_enabled' => 'boolean',
            'geo_countries' => 'nullable|array',
        ]);
        
        $site->update($validated);
        
        return redirect()
            ->route('admin.sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Delete a site
     */
    public function destroy(Site $site)
    {
        $customerName = $site->customer->name;
        $domain = $site->domain;
        
        $site->delete();
        
        return redirect()
            ->route('admin.sites.index')
            ->with('success', "Site {$domain} for {$customerName} deleted.");
    }

    /**
     * Show settings editor (full settings form)
     */
    public function settings(Site $site)
    {
        $site->load('settings');
        
        // Create settings if doesn't exist
        if (!$site->settings) {
            SiteSettings::create(['site_id' => $site->id]);
            $site->load('settings');
        }
        
        return view('admin.sites.settings', compact('site'));
    }

    /**
     * Update all site settings
     */
    public function updateSettings(Request $request, Site $site)
    {
        $validated = $request->validate([
            // Banner Appearance
            'banner_position' => 'required|in:bottom,top,bottom-left,bottom-right,center',
            'banner_layout' => 'required|in:bar,box,popup',
            'primary_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'text_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'bg_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'button_style' => 'required|in:rounded,square,pill',
            
            // Banner Content
            'heading' => 'required|string|max:255',
            'message' => 'nullable|string|max:1000',
            'accept_all_text' => 'required|string|max:50',
            'reject_all_text' => 'required|string|max:50',
            'customize_text' => 'required|string|max:50',
            'save_preferences_text' => 'required|string|max:50',
            'privacy_policy_url' => 'nullable|url|max:500',
            'privacy_policy_text' => 'required|string|max:100',
            
            // Category Labels
            'category_labels' => 'nullable|array',
            'category_labels.*' => 'string|max:100',
            'category_descriptions' => 'nullable|array',
            'category_descriptions.*' => 'string|max:500',
            
            // Behavior
            'show_reject_all' => 'boolean',
            'show_close_button' => 'boolean',
            'close_on_scroll' => 'boolean',
            'close_on_timeout' => 'boolean',
            'timeout_seconds' => 'integer|min:0|max:300',
            'reload_on_consent' => 'boolean',
            'consent_expiry_days' => 'required|integer|min:1|max:730',
            'reconsent_days' => 'required|integer|min:1|max:730',
            
            // Script Blocking
            'auto_block_scripts' => 'boolean',
            'custom_script_patterns' => 'nullable|array',
            'script_whitelist' => 'nullable|array',
            
            // Advanced
            'respect_dnt' => 'boolean',
            'log_consents' => 'boolean',
            'custom_css' => 'nullable|string|max:5000',
            'custom_js' => 'nullable|string|max:5000',
            
            // TCF Settings
            'tcf_purposes' => 'nullable|array',
            'tcf_vendors' => 'nullable|array',
            'tcf_legitimate_interests' => 'nullable|array',
            
            // GCM Settings
            'gcm_default_state' => 'nullable|array',
            'gcm_wait_for_update' => 'boolean',
            'gcm_wait_timeout_ms' => 'integer|min:0|max:5000',
            'gcm_region_settings' => 'nullable|array',
        ]);
        
        // Ensure boolean fields have a value
        $booleanFields = [
            'show_reject_all', 'show_close_button', 'close_on_scroll',
            'close_on_timeout', 'reload_on_consent', 'auto_block_scripts',
            'respect_dnt', 'log_consents', 'gcm_wait_for_update'
        ];
        
        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field);
        }
        
        // Update or create settings
        $site->settings()->updateOrCreate(
            ['site_id' => $site->id],
            $validated
        );
        
        return redirect()
            ->route('admin.sites.settings', $site)
            ->with('success', 'Settings saved successfully.');
    }

    /**
     * Regenerate site token
     */
    public function regenerateToken(Site $site)
    {
        $site->update(['site_token' => Str::random(64)]);
        
        return redirect()
            ->route('admin.sites.show', $site)
            ->with('success', 'Site token regenerated. Update your WordPress plugin with the new token.');
    }

    /**
     * Increment policy version (force re-consent)
     */
    public function incrementPolicy(Site $site)
    {
        $site->incrementPolicyVersion();
        
        return redirect()
            ->route('admin.sites.show', $site)
            ->with('success', 'Policy version incremented. Users will be asked for consent again.');
    }

    /**
     * View site cookies
     */
    public function cookies(Site $site)
    {
        $site->load('cookies');
        
        return view('admin.sites.cookies', compact('site'));
    }

    /**
     * View consent records for a site
     */
    public function consents(Request $request, Site $site)
    {
        $query = $site->consentRecords()->latest();
        
        if ($request->filled('method')) {
            $query->where('consent_method', $request->method);
        }
        
        if ($request->filled('country')) {
            $query->where('country_code', $request->country);
        }
        
        $consents = $query->paginate(50)->withQueryString();
        
        return view('admin.sites.consents', compact('site', 'consents'));
    }

    /**
     * View analytics for a site
     */
    public function analytics(Request $request, Site $site)
    {
        $allowedPeriods = [7, 30, 90, 365];
        $period = (int) $request->integer('period', 30);

        if (!in_array($period, $allowedPeriods, true)) {
            $period = 30;
        }

        $endDate = now()->endOfDay();
        $startDate = now()->subDays($period - 1)->startOfDay();
        $previousStartDate = $startDate->copy()->subDays($period);
        $previousEndDate = $startDate->copy()->subSecond();

        $sessions = $site->sessions()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        $previousSessions = $site->sessions()
            ->whereBetween('date', [$previousStartDate->toDateString(), $previousEndDate->toDateString()])
            ->get();

        $recentConsents = $site->consentRecords()
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->take(10)
            ->get();

        $usage = $site->usage()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        $totalSessions = (int) $sessions->sum('total_sessions');
        $bannerShown = (int) $sessions->sum('banner_shown');
        $acceptAllCount = (int) $sessions->sum('consent_given');
        $rejectAllCount = (int) $sessions->sum('consent_denied');
        $customCount = (int) $sessions->sum('consent_customized');
        $totalConsents = $acceptAllCount + $rejectAllCount + $customCount;
        $noInteractionCount = max((int) $sessions->sum('no_action'), max(0, $bannerShown - $totalConsents));

        $consentRate = $bannerShown > 0
            ? round(($totalConsents / $bannerShown) * 100, 1)
            : null;

        $acceptAllRate = $totalConsents > 0
            ? round(($acceptAllCount / $totalConsents) * 100, 1)
            : 0.0;

        $previousTotalSessions = (int) $previousSessions->sum('total_sessions');
        $sessionChange = $this->calculateTrendPercentage($totalSessions, $previousTotalSessions);

        $currentMonthSessions = $usage?->total_sessions ?? $site->getCurrentMonthSessions();
        $sessionLimit = $usage?->session_limit ?? $site->getSessionLimit();

        $analytics = [
            'total_sessions' => $totalSessions,
            'session_change' => $sessionChange,
            'total_consents' => $totalConsents,
            'consent_rate' => $consentRate,
            'has_banner_data' => $bannerShown > 0,
            'accept_all_count' => $acceptAllCount,
            'accept_all_rate' => $acceptAllRate,
            'reject_all_count' => $rejectAllCount,
            'custom_count' => $customCount,
            'no_interaction_count' => $noInteractionCount,
            'sessions_used' => $currentMonthSessions,
            'sessions_limit' => $sessionLimit,
            'usage_percentage' => $sessionLimit > 0
                ? round(min(100, ($currentMonthSessions / $sessionLimit) * 100), 1)
                : 100.0,
            'category_rates' => $this->buildCategoryRates($sessions, $totalConsents),
            'sessions_labels' => $this->buildSessionLabels($sessions, $startDate, $endDate),
            'sessions_data' => $this->buildSessionData($sessions, $startDate, $endDate),
            'gcm_stats' => $this->buildGcmStats($site, $startDate),
            'top_countries' => $this->aggregateBreakdown($sessions, 'geo_breakdown'),
            'device_breakdown' => $this->aggregateBreakdown($sessions, 'device_breakdown'),
        ];

        return view('admin.sites.analytics', compact('site', 'analytics', 'recentConsents', 'period'));
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
                'necessary' => 100.0,
                'functional' => 0.0,
                'analytics' => 0.0,
                'marketing' => 0.0,
            ];
        }

        return [
            'necessary' => 100.0,
            'functional' => round(($sessions->sum('accepted_functional') / $totalConsents) * 100, 1),
            'analytics' => round(($sessions->sum('accepted_analytics') / $totalConsents) * 100, 1),
            'marketing' => round(($sessions->sum('accepted_marketing') / $totalConsents) * 100, 1),
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
        $sessionsByDate = $sessions->keyBy(fn ($session) => $session->date->toDateString());
        $data = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $data[] = (int) ($sessionsByDate[$date->toDateString()]->total_sessions ?? 0);
        }

        return $data;
    }

    private function buildGcmStats(Site $site, $startDate): array
    {
        $keys = ['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization'];
        $stats = [];

        foreach ($keys as $key) {
            $stats[$key] = ['granted' => 0, 'total' => 0];
        }

        $site->consentRecords()
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('gcm_state')
            ->select(['id', 'gcm_state'])
            ->orderBy('id')
            ->chunkById(500, function ($records) use (&$stats, $keys) {
                foreach ($records as $record) {
                    foreach ($keys as $key) {
                        if (!array_key_exists($key, $record->gcm_state ?? [])) {
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
