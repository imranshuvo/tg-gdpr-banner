<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\License;
use App\Models\Site;
use App\Models\SiteSettings;
use App\Services\Analytics\SiteAnalyticsService;
use App\Services\Compliance\GdprReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function __construct(
        private SiteAnalyticsService $analytics,
        private GdprReportService $gdprReport,
    ) {}

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
            'geo_targeting_mode' => ['required', Rule::in(['all', 'eu', 'selected'])],
            'geo_countries' => 'nullable|array|max:32|required_if:geo_targeting_mode,selected',
            'geo_countries.*' => ['string', 'size:2', Rule::in(Site::EUROPEAN_COUNTRY_CODES)],
        ]);

        [$validated['geo_targeting_enabled'], $validated['geo_countries']] = $this->normalizeGeoTargetingSettings(
            $validated['geo_targeting_mode'],
            $validated['geo_countries'] ?? []
        );

        unset($validated['geo_targeting_mode']);
        
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
            'geo_targeting_mode' => ['required', Rule::in(['all', 'eu', 'selected'])],
            'geo_countries' => 'nullable|array|max:32|required_if:geo_targeting_mode,selected',
            'geo_countries.*' => ['string', 'size:2', Rule::in(Site::EUROPEAN_COUNTRY_CODES)],
        ]);

        [$validated['geo_targeting_enabled'], $validated['geo_countries']] = $this->normalizeGeoTargetingSettings(
            $validated['geo_targeting_mode'],
            $validated['geo_countries'] ?? []
        );

        unset($validated['geo_targeting_mode']);
        
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
            'geo_targeting_mode' => ['required', Rule::in(['all', 'eu', 'selected'])],
            'geo_countries' => 'nullable|array|max:32|required_if:geo_targeting_mode,selected',
            'geo_countries.*' => ['string', 'size:2', Rule::in(Site::EUROPEAN_COUNTRY_CODES)],

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

        [$geoTargetingEnabled, $geoCountries] = $this->normalizeGeoTargetingSettings(
            $validated['geo_targeting_mode'],
            $validated['geo_countries'] ?? []
        );

        $site->update([
            'geo_targeting_enabled' => $geoTargetingEnabled,
            'geo_countries' => $geoCountries,
        ]);

        unset($validated['geo_targeting_mode'], $validated['geo_countries']);
        
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
     * Normalize geo targeting settings to the persisted site fields.
     *
     * @param string $mode
     * @param array $countries
     * @return array{0: bool, 1: array<int, string>}
     */
    private function normalizeGeoTargetingSettings(string $mode, array $countries): array
    {
        if ($mode === 'all') {
            return [false, []];
        }

        if ($mode === 'eu') {
            return [true, ['EU']];
        }

        $selectedCountries = array_values(array_unique(array_map('strtoupper', $countries)));

        return [true, $selectedCountries];
    }

    /**
     * Regenerate site token
     */
    public function regenerateToken(Site $site)
    {
        $site->update(['site_token' => Str::random(64)]);
        
        return redirect()
            ->route('admin.sites.show', $site)
            ->with('success', 'Site token regenerated. Update your site integration with the new token.');
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
     * View analytics for a site. Heavy lifting lives in SiteAnalyticsService —
     * this controller just resolves the period and hands off.
     */
    public function analytics(Request $request, Site $site)
    {
        $period = (int) $request->integer('period', 30);
        $analytics = $this->analytics->forSite($site, $period);
        $recentConsents = $this->analytics->recentConsents($site, $period);

        if (! in_array($period, SiteAnalyticsService::ALLOWED_PERIODS, true)) {
            $period = 30;
        }

        return view('admin.sites.analytics', compact('site', 'analytics', 'recentConsents', 'period'));
    }

    /**
     * Per-site GDPR compliance report (super-admin view).
     *
     * Same payload as the customer-facing report, served from the admin
     * side so super-admins can audit any tenant's compliance posture
     * without having to impersonate the customer.
     */
    public function gdprReport(Request $request, Site $site)
    {
        $period = (int) $request->integer('period', 90);
        $report = $this->gdprReport->forSite($site, $period);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            $filename = 'gdpr-report-' . Str::slug($site->domain) . '-' . now()->format('Y-m-d') . '.json';
            return response()->json($report, 200, [
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return view('admin.sites.gdpr-report', [
            'site'   => $site,
            'report' => $report,
            'period' => in_array($period, GdprReportService::ALLOWED_PERIODS, true) ? $period : 90,
        ]);
    }
}
