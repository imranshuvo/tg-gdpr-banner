@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Customers -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Customers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_customers'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Licenses -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Active Licenses</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['active_licenses'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">of {{ $stats['total_licenses'] }} total</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-key text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Activations -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Active Sites</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_activations'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-globe text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Revenue YTD -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Revenue (YTD)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($stats['revenue_ytd']) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Expiring Soon</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['expiring_soon'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">within 30 days</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Licenses -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Licenses</h3>
                <a href="{{ route('admin.licenses.index') }}" class="text-blue-600 hover:text-blue-700 text-sm">
                    View All →
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recent_licenses as $license)
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $license->customer->name }}</p>
                                <p class="text-sm text-gray-500">{{ $license->license_key }}</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($license->status) }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ ucfirst($license->plan) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No licenses yet</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- License Distribution -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Plan Distribution</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @php
                        $total = array_sum($plan_distribution->toArray());
                    @endphp
                    
                    @foreach(['single' => 'Single Site ($59)', '3-sites' => '3 Sites ($99)', '10-sites' => '10 Sites ($199)'] as $key => $label)
                        @php
                            $count = $plan_distribution[$key] ?? 0;
                            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $label }}</span>
                                <span class="text-gray-500">{{ $count }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
