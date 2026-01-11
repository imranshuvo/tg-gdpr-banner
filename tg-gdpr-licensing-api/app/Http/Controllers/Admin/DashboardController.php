<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\License;
use App\Models\Activation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Overall statistics
        $stats = [
            'total_customers' => Customer::count(),
            'total_licenses' => License::count(),
            'active_licenses' => License::where('status', 'active')->count(),
            'total_activations' => Activation::where('status', 'active')->count(),
            'revenue_ytd' => $this->calculateRevenue(),
            'expiring_soon' => License::where('status', 'active')
                ->where('expires_at', '<=', now()->addDays(30))
                ->count(),
        ];

        // Recent customers
        $recent_customers = Customer::latest()->take(5)->get();

        // Recent licenses
        $recent_licenses = License::with('customer')
            ->latest()
            ->take(10)
            ->get();

        // License status breakdown
        $license_breakdown = License::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Plan distribution
        $plan_distribution = License::select('plan', DB::raw('count(*) as count'))
            ->where('status', 'active')
            ->groupBy('plan')
            ->get()
            ->pluck('count', 'plan');

        return view('admin.dashboard', compact(
            'stats',
            'recent_customers',
            'recent_licenses',
            'license_breakdown',
            'plan_distribution'
        ));
    }

    private function calculateRevenue()
    {
        // Simple revenue calculation based on active licenses
        $revenue = 0;
        $prices = [
            'single' => 59,
            '3-sites' => 99,
            '10-sites' => 199,
        ];

        $licenses = License::where('status', 'active')
            ->where('expires_at', '>=', now()->startOfYear())
            ->get();

        foreach ($licenses as $license) {
            $revenue += $prices[$license->plan] ?? 0;
        }

        return $revenue;
    }
}
