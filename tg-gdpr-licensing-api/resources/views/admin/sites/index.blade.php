@extends('layouts.admin')

@section('title', 'Sites Management')
@section('page-title', 'Sites')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">All Sites</h2>
            <p class="text-sm text-gray-500">Manage every CMP tenant site across all customers.</p>
        </div>
        <a href="{{ route('admin.sites.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
            <i class="fas fa-plus mr-2"></i> Add site
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" name="search" placeholder="Domain, name, URL…" value="{{ request('search') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">All status</option>
                    @foreach (['active' => 'Active', 'trial' => 'Trial', 'paused' => 'Paused', 'expired' => 'Expired'] as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Customer</label>
                <select name="customer_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">Filter</button>
                <a href="{{ route('admin.sites.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3">Site</th>
                    <th class="px-6 py-3">Customer</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Sessions (this month)</th>
                    <th class="px-6 py-3">Last scan</th>
                    <th class="px-6 py-3">Created</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($sites as $site)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $site->domain }}</div>
                            <div class="text-xs text-gray-500">{{ $site->site_name ?? $site->site_url }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.customers.show', $site->customer) }}" class="text-blue-600 hover:text-blue-800">{{ $site->customer->name }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @switch($site->status)
                                @case('active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @break
                                @case('trial')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Trial @if ($site->trial_ends_at)<span class="ml-1 text-blue-700">· {{ $site->trial_ends_at->diffForHumans() }}</span>@endif
                                    </span>
                                    @break
                                @case('paused')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Paused</span>
                                    @break
                                @case('expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $site->status }}</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @php
                                $sessions = $site->getCurrentMonthSessions();
                                $limit    = $site->getSessionLimit();
                                $pct      = $limit > 0 ? min(100, ($sessions / $limit) * 100) : 0;
                                $barColor = $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            <div class="flex items-center gap-2">
                                <div class="w-24 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="{{ $barColor }} h-full" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 whitespace-nowrap">{{ number_format($sessions) }} / {{ number_format($limit) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($site->last_scan_at)
                                <div class="text-gray-700">{{ $site->last_scan_at->diffForHumans() }}</div>
                                <div class="text-xs text-gray-500">{{ $site->cookies_detected }} cookies</div>
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $site->created_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.sites.show', $site) }}" title="View" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.sites.settings', $site) }}" title="Settings" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded">
                                    <i class="fas fa-cog"></i>
                                </a>
                                <a href="{{ route('admin.sites.analytics', $site) }}" title="Analytics" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="{{ route('admin.sites.edit', $site) }}" title="Edit" class="p-2 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            <i class="fas fa-globe text-4xl text-gray-300 mb-3 block"></i>
                            No sites found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($sites->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">{{ $sites->links() }}</div>
        @endif
    </div>
</div>
@endsection
