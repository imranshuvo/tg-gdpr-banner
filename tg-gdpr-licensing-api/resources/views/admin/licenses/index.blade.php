@extends('layouts.admin')

@section('title', 'Licenses')
@section('page-title', 'Licenses')

@section('content')
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.licenses.index') }}" class="flex flex-wrap gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search licenses..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Status Filter -->
            <div class="w-40">
                <select name="status" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <!-- Plan Filter -->
            <div class="w-40">
                <select name="plan" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Plans</option>
                    <option value="single" {{ request('plan') == 'single' ? 'selected' : '' }}>Single Site</option>
                    <option value="triple" {{ request('plan') == 'triple' ? 'selected' : '' }}>3 Sites</option>
                    <option value="ten" {{ request('plan') == 'ten' ? 'selected' : '' }}>10 Sites</option>
                </select>
            </div>

            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Filter
            </button>

            @if(request()->hasAny(['search', 'status', 'plan']))
                <a href="{{ route('admin.licenses.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-600">Total Licenses</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-600">Active</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-600">Expiring Soon</div>
            <div class="mt-2 text-3xl font-bold text-orange-600">{{ $stats['expiring'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-600">Expired</div>
            <div class="mt-2 text-3xl font-bold text-red-600">{{ $stats['expired'] }}</div>
        </div>
    </div>

    <!-- Licenses Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold">All Licenses</h2>
            <a href="{{ route('admin.licenses.create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Create License
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">
                                        {{ $license->license_key }}
                                    </code>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.customers.show', $license->customer) }}" 
                                   class="text-blue-600 hover:underline">
                                    {{ $license->customer->name }}
                                </a>
                                <div class="text-sm text-gray-500">{{ $license->customer->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($license->plan_type === 'single')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Single Site
                                    </span>
                                @elseif($license->plan_type === 'triple')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        3 Sites
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                        10 Sites
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($license->status === 'active')
                                    @if($license->isExpired())
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Expired
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm">
                                    {{ $license->activations->count() }} / {{ $license->max_activations }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($license->expires_at)
                                    @php
                                        $daysUntilExpiry = now()->diffInDays($license->expires_at, false);
                                    @endphp
                                    @if($daysUntilExpiry < 0)
                                        <span class="text-red-600 font-medium">
                                            {{ $license->expires_at->format('M d, Y') }}
                                        </span>
                                    @elseif($daysUntilExpiry <= 30)
                                        <span class="text-orange-600 font-medium">
                                            {{ $license->expires_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-900">
                                            {{ $license->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-500">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.licenses.show', $license) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        View
                                    </a>
                                    <a href="{{ route('admin.licenses.edit', $license) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No licenses found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($licenses->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $licenses->links() }}
            </div>
        @endif
    </div>
@endsection
