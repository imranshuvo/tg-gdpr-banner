@extends('layouts.admin')

@section('title', 'Plans')
@section('page-title', 'Plans')

@section('content')
<div class="max-w-6xl space-y-4">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600">
            Plans displayed on the public pricing page and on customer checkout. Provider price IDs are paired with each plan
            so checkout can hand the right ID to Stripe / Frisbii based on the active mode.
        </p>
        <a href="{{ route('admin.plans.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
            <i class="fas fa-plus mr-2"></i> New plan
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3">Order</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Sites</th>
                    <th class="px-6 py-3">Price</th>
                    <th class="px-6 py-3">Stripe IDs</th>
                    <th class="px-6 py-3">Frisbii IDs</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($plans as $plan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $plan->sort_order }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $plan->name }}
                                @if ($plan->is_popular)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">popular</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 font-mono">{{ $plan->slug }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $plan->max_sites }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $plan->display_price }}<span class="text-gray-400">{{ $plan->display_period }}</span></td>
                        <td class="px-6 py-4 text-xs font-mono">
                            <div class="text-gray-500">test: {{ $plan->stripe_price_id_test ?: '—' }}</div>
                            <div class="text-gray-500">live: {{ $plan->stripe_price_id_live ?: '—' }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs font-mono">
                            <div class="text-gray-500">test: {{ $plan->frisbii_plan_id_test ?: '—' }}</div>
                            <div class="text-gray-500">live: {{ $plan->frisbii_plan_id_live ?: '—' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($plan->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Hidden</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline" onsubmit="return confirm('Delete this plan? This does not affect existing subscriptions.');">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No plans yet — <a href="{{ route('admin.plans.create') }}" class="text-blue-600">create one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
