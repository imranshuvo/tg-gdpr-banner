<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Customer;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $query = License::with(['customer', 'activations']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan_type', $request->plan);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('license_key', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $licenses = $query->latest()->paginate(20)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => License::count(),
            'active' => License::where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })->count(),
            'expiring' => License::where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(30)])
                ->count(),
            'expired' => License::where('expires_at', '<=', now())->count(),
        ];

        return view('admin.licenses.index', compact('licenses', 'stats'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        
        return view('admin.licenses.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_type' => 'required|in:single,triple,ten',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
        ]);

        $license = $this->licenseService->createLicense(
            $validated['customer_id'],
            $validated['plan_type'],
            $validated['expires_at'] ?? now()->addYear()
        );

        // Update status if not active
        if ($validated['status'] !== 'active') {
            $license->update(['status' => $validated['status']]);
        }

        return redirect()
            ->route('admin.licenses.show', $license)
            ->with('success', 'License created successfully!');
    }

    public function show(License $license)
    {
        $license->load(['customer', 'activations']);

        return view('admin.licenses.show', compact('license'));
    }

    public function edit(License $license)
    {
        return view('admin.licenses.edit', compact('license'));
    }

    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'plan_type' => 'required|in:single,triple,ten',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
        ]);

        $license->update($validated);

        return redirect()
            ->route('admin.licenses.show', $license)
            ->with('success', 'License updated successfully!');
    }

    public function destroy(License $license)
    {
        // Deactivate all activations first
        $license->activations()->update(['status' => 'inactive']);
        
        $license->delete();

        return redirect()
            ->route('admin.licenses.index')
            ->with('success', 'License deleted successfully!');
    }

    public function revoke(License $license)
    {
        $license->update(['status' => 'suspended']);
        $license->activations()->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.licenses.show', $license)
            ->with('success', 'License revoked successfully!');
    }

    public function extend(License $license)
    {
        $license->update([
            'expires_at' => $license->expires_at->addYear(),
        ]);

        return redirect()
            ->route('admin.licenses.show', $license)
            ->with('success', 'License extended by 1 year!');
    }

    public function deactivateSite(License $license, $activation)
    {
        $activation = $license->activations()->findOrFail($activation);
        $activation->delete();

        return redirect()
            ->route('admin.licenses.show', $license)
            ->with('success', 'Site deactivated successfully!');
    }
}
