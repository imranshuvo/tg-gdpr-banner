<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\Site;
use App\Services\Analytics\SiteAnalyticsService;
use App\Services\Logging\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Customer-facing per-site management.
 *
 * Scope: every query is filtered to the authenticated user's customer_id.
 * A customer can never see or act on a Site that doesn't belong to their
 * Customer record. The implicit scope works on the current single-user-per-
 * customer model; the planned post-launch refactor swaps it for a
 * `customer_user` pivot + policy classes.
 */
class SiteController extends Controller
{
    public function __construct(
        private SiteAnalyticsService $analytics,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * List all sites belonging to the authed customer, with quick stats.
     */
    public function index(Request $request)
    {
        $customer = $this->customerOrAbort();

        // Activations live on License (not Site directly), so we eager-load
        // license.activations and surface counts in the view from there.
        $sites = $customer->sites()
            ->with(['license:id,license_key,plan,status,max_activations', 'license.activations'])
            ->withCount(['consentRecords as consents_count_total'])
            ->withCount(['consentRecords as consents_count_30d' => function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('customer.sites.index', compact('sites', 'customer'));
    }

    /**
     * Site detail: activations, recent consents, key metadata.
     */
    public function show(Site $site)
    {
        $this->authorizeSite($site);

        $site->load(['license:id,license_key,plan,status,max_activations', 'license.activations', 'settings']);

        $recentConsents = $site->consentRecords()
            ->latest()
            ->take(20)
            ->get(['id', 'consent_id', 'consent_method', 'consent_categories', 'country_code', 'device_type', 'created_at']);

        return view('customer.sites.show', compact('site', 'recentConsents'));
    }

    /**
     * Per-site analytics dashboard.
     */
    public function analytics(Request $request, Site $site)
    {
        $this->authorizeSite($site);

        $period    = (int) $request->integer('period', 30);
        $payload   = $this->analytics->forSite($site, $period);
        $recent    = $this->analytics->recentConsents($site, $period);

        return view('customer.sites.analytics', [
            'site'            => $site,
            'analytics'       => $payload,
            'recentConsents'  => $recent,
            'period'          => in_array($period, SiteAnalyticsService::ALLOWED_PERIODS, true) ? $period : 30,
        ]);
    }

    /**
     * Free up an activation slot by marking it inactive.
     *
     * The customer is choosing to detach a previously-active WordPress
     * domain from this license — opens the slot for a new site to activate.
     * Audit-logged so we have a trail of customer-driven deactivations.
     */
    public function deactivateActivation(Request $request, Site $site, Activation $activation)
    {
        $this->authorizeSite($site);

        // Make sure the activation actually belongs to this site's license.
        abort_if($activation->license_id !== $site->license_id, 404);

        $activation->update(['status' => 'inactive']);

        $this->activityLogger->log(
            description: "Customer deactivated activation: {$activation->domain}",
            subject:     $site,
            properties:  [
                'activation_id' => $activation->id,
                'domain'        => $activation->domain,
                'license_key'   => $site->license?->license_key,
            ],
            event:   'site.activation.deactivated',
            logName: 'site',
        );

        return redirect()
            ->route('customer.sites.show', $site)
            ->with('success', "Deactivated {$activation->domain}. The slot is now free for another site.");
    }

    /** ─────────────────────────────────────────────────────────────────── */

    private function customerOrAbort()
    {
        $customer = Auth::user()->customer;
        abort_if(! $customer, 403, 'No customer profile associated with this account.');
        return $customer;
    }

    /**
     * Cross-customer access guard. Once Phase 2 lands this becomes a Policy.
     */
    private function authorizeSite(Site $site): void
    {
        $customerId = Auth::user()->customer_id;
        abort_if($site->customer_id !== $customerId, 403, 'You do not have access to this site.');
    }
}
