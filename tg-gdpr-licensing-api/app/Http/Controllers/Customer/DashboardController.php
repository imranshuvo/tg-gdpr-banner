<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the customer dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Get licenses with activations
        $licenses = $customer->licenses()
            ->with('activations')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $stats = [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->where('status', 'active')->count(),
            'expired_licenses' => $licenses->filter(fn($l) => $l->isExpired())->count(),
            'total_activations' => $licenses->sum(fn($l) => $l->activations->where('status', 'active')->count()),
            'max_activations' => $licenses->sum('max_activations'),
        ];

        // Get recent activity
        $recentActivity = $this->activityLogger->getForSubject($customer, 10);

        // Get subscription info if using Cashier
        $subscription = $user->subscriptions()->active()->first();

        // Log dashboard access
        $this->activityLogger->logCustomer(
            'dashboard_viewed',
            $customer,
            ['ip' => $request->ip()]
        );

        return view('customer.dashboard', compact(
            'customer',
            'licenses',
            'stats',
            'recentActivity',
            'subscription'
        ));
    }
}
