@extends('layouts.admin')

@section('title', $customer->name)
@section('page-title', $customer->name)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Details -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Customer Details</h3>
                    <a href="{{ route('admin.customers.edit', $customer) }}" 
                       class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Company</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->company ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this customer? This will also delete all associated licenses.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Delete Customer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Licenses -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Licenses ({{ $customer->licenses->count() }})</h3>
                    <a href="{{ route('admin.licenses.create', ['customer_id' => $customer->id]) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm">
                        <i class="fas fa-plus mr-1"></i>
                        New License
                    </a>
                </div>

                <div class="divide-y divide-gray-200">
                    @forelse($customer->licenses as $license)
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">
                                            {{ $license->license_key }}
                                        </code>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : 
                                               ($license->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($license->status) }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            {{ ucfirst(str_replace('-', ' ', $license->plan)) }}
                                        </span>
                                    </div>

                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div>
                                            <i class="fas fa-calendar-alt text-gray-400 w-4"></i>
                                            Created: {{ $license->created_at->format('M d, Y') }}
                                        </div>
                                        <div>
                                            <i class="fas fa-clock text-gray-400 w-4"></i>
                                            Expires: {{ $license->expires_at->format('M d, Y') }}
                                            @if($license->expires_at->isPast())
                                                <span class="text-red-600">(Expired)</span>
                                            @elseif($license->expires_at->diffInDays() < 30)
                                                <span class="text-orange-600">({{ $license->expires_at->diffInDays() }} days left)</span>
                                            @endif
                                        </div>
                                        <div>
                                            <i class="fas fa-globe text-gray-400 w-4"></i>
                                            Active Sites: {{ $license->activations->where('status', 'active')->count() }} / {{ $license->max_activations }}
                                        </div>
                                    </div>

                                    @if($license->activations->where('status', 'active')->count() > 0)
                                        <div class="mt-3">
                                            <p class="text-xs font-medium text-gray-500 mb-1">Activated Domains:</p>
                                            <div class="space-y-1">
                                                @foreach($license->activations->where('status', 'active') as $activation)
                                                    <div class="text-xs text-gray-600 flex items-center">
                                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                                        {{ $activation->domain }}
                                                        <span class="text-gray-400 ml-2">
                                                            (last checked: {{ $activation->last_check_at ? $activation->last_check_at->diffForHumans() : 'never' }})
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('admin.licenses.show', $license) }}" 
                                   class="text-blue-600 hover:text-blue-700 ml-4">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center text-gray-500">
                            <i class="fas fa-key text-4xl text-gray-300 mb-2"></i>
                            <p>No licenses yet</p>
                            <a href="{{ route('admin.licenses.create', ['customer_id' => $customer->id]) }}" 
                               class="text-blue-600 hover:text-blue-700 mt-2 inline-block">
                                Create first license
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
