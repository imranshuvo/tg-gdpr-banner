@extends('layouts.admin')

@section('title', 'License Details')
@section('page-title', 'License Details')

@section('content')
    <div class="space-y-6">
        <!-- License Info Card -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex justify-between items-start">
                <div class="flex-1">
                    <h2 class="text-lg font-semibold mb-2">License Information</h2>
                    <code class="px-3 py-2 bg-gray-100 rounded text-lg font-mono">
                        {{ $license->license_key }}
                    </code>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.licenses.edit', $license) }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Edit
                    </a>
                    @if($license->status === 'active')
                        <form method="POST" 
                              action="{{ route('admin.licenses.revoke', $license) }}"
                              onsubmit="return confirm('Are you sure you want to revoke this license?');">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Revoke
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.licenses.update', $license) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="active">
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Activate
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Customer</div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('admin.customers.show', $license->customer) }}" 
                           class="text-lg font-medium text-blue-600 hover:underline">
                            {{ $license->customer->name }}
                        </a>
                    </div>
                    <div class="text-sm text-gray-600">{{ $license->customer->email }}</div>
                    @if($license->customer->company)
                        <div class="text-sm text-gray-600">{{ $license->customer->company }}</div>
                    @endif
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Plan Type</div>
                    <div class="mt-1">
                        @if($license->plan_type === 'single')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                Single Site
                            </span>
                        @elseif($license->plan_type === 'triple')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                                3 Sites
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                10 Sites
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Status</div>
                    <div class="mt-1">
                        @if($license->status === 'active')
                            @if($license->isExpired())
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                    Expired
                                </span>
                            @else
                                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                Suspended
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Activations</div>
                    <div class="text-lg font-medium">
                        {{ $license->activations->count() }} / {{ $license->max_activations }}
                    </div>
                    @if($license->activations->count() >= $license->max_activations)
                        <div class="text-sm text-red-600">Limit reached</div>
                    @else
                        <div class="text-sm text-gray-600">
                            {{ $license->max_activations - $license->activations->count() }} remaining
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Created</div>
                    <div class="text-lg">{{ $license->created_at->format('M d, Y') }}</div>
                    <div class="text-sm text-gray-600">{{ $license->created_at->diffForHumans() }}</div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 mb-1">Expires</div>
                    @if($license->expires_at)
                        @php
                            $daysUntilExpiry = now()->diffInDays($license->expires_at, false);
                        @endphp
                        <div class="text-lg {{ $daysUntilExpiry < 0 ? 'text-red-600' : ($daysUntilExpiry <= 30 ? 'text-orange-600' : '') }}">
                            {{ $license->expires_at->format('M d, Y') }}
                        </div>
                        @if($daysUntilExpiry < 0)
                            <div class="text-sm text-red-600 font-medium">
                                Expired {{ abs($daysUntilExpiry) }} days ago
                            </div>
                        @elseif($daysUntilExpiry <= 30)
                            <div class="text-sm text-orange-600 font-medium">
                                Expires in {{ $daysUntilExpiry }} days
                            </div>
                            <form method="POST" 
                                  action="{{ route('admin.licenses.extend', $license) }}"
                                  class="mt-2">
                                @csrf
                                <button type="submit" 
                                        class="text-sm text-blue-600 hover:underline">
                                    Extend by 1 year
                                </button>
                            </form>
                        @else
                            <div class="text-sm text-gray-600">{{ $license->expires_at->diffForHumans() }}</div>
                        @endif
                    @else
                        <div class="text-lg">Never</div>
                        <div class="text-sm text-gray-600">Lifetime license</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Activations Card -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Active Sites</h2>
            </div>

            @if($license->activations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activated</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($license->activations as $activation)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                            </svg>
                                            <span class="font-medium">{{ $activation->domain }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $activation->ip_address }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $activation->created_at->format('M d, Y') }}
                                        <div class="text-xs text-gray-500">{{ $activation->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $hoursSinceLastSeen = $activation->last_checked_at->diffInHours(now());
                                        @endphp
                                        @if($hoursSinceLastSeen < 24)
                                            <span class="text-green-600">
                                                {{ $activation->last_checked_at->diffForHumans() }}
                                            </span>
                                        @elseif($hoursSinceLastSeen < 168)
                                            <span class="text-orange-600">
                                                {{ $activation->last_checked_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-red-600">
                                                {{ $activation->last_checked_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <form method="POST" 
                                              action="{{ route('admin.licenses.deactivate-site', [$license, $activation]) }}"
                                              onsubmit="return confirm('Are you sure you want to deactivate this site?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900">
                                                Deactivate
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                    <p>No active sites yet</p>
                    <p class="text-sm mt-1">This license hasn't been activated on any domains</p>
                </div>
            @endif
        </div>

        <!-- Activity Log (placeholder for future) -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Recent Activity</h2>
            </div>
            <div class="p-12 text-center text-gray-500">
                <p class="text-sm">Activity logging coming soon...</p>
            </div>
        </div>
    </div>
@endsection
