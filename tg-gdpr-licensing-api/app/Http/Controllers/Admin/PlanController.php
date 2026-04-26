<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan()]);
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validated($request));
        return redirect()->route('admin.plans.index')->with('success', 'Plan created.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));
        return redirect()->route('admin.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plan removed.');
    }

    private function validated(Request $request, ?Plan $existing = null): array
    {
        $data = $request->validate([
            'slug'                  => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/',
                                        \Illuminate\Validation\Rule::unique('plans', 'slug')->ignore($existing?->id)],
            'name'                  => 'required|string|max:120',
            'description'           => 'nullable|string|max:255',
            'features_raw'          => 'nullable|string|max:4000',
            'max_sites'             => 'required|integer|min:1|max:1000',
            'display_price'         => 'nullable|string|max:32',
            'display_period'        => 'nullable|string|max:32',
            'stripe_price_id_test'  => 'nullable|string|max:120',
            'stripe_price_id_live'  => 'nullable|string|max:120',
            'frisbii_plan_id_test'  => 'nullable|string|max:120',
            'frisbii_plan_id_live'  => 'nullable|string|max:120',
            'is_popular'            => 'sometimes|boolean',
            'is_active'             => 'sometimes|boolean',
            'sort_order'            => 'nullable|integer|min:0|max:9999',
        ]);

        // Parse features textarea -> JSON array. One feature per non-empty line.
        $features = collect(preg_split('/\r?\n/', (string) ($data['features_raw'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
        unset($data['features_raw']);
        $data['features'] = $features;

        $data['is_popular'] = (bool) ($data['is_popular'] ?? false);
        $data['is_active']  = (bool) ($data['is_active']  ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
