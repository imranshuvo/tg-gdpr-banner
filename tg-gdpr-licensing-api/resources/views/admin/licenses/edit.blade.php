@extends('layouts.admin')

@section('title', 'Edit License')
@section('page-title', 'Edit License')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.licenses.update', $license) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- License Key (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            License Key
                        </label>
                        <code class="block w-full px-4 py-2 bg-gray-100 rounded-lg text-sm font-mono">
                            {{ $license->license_key }}
                        </code>
                        <p class="mt-1 text-sm text-gray-500">License keys cannot be changed</p>
                    </div>

                    <!-- Customer (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Customer
                        </label>
                        <div class="px-4 py-2 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="font-medium">{{ $license->customer->name }}</div>
                            <div class="text-sm text-gray-600">{{ $license->customer->email }}</div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            To change the customer, <a href="{{ route('admin.customers.show', $license->customer) }}" class="text-blue-600 hover:underline">transfer the license</a>
                        </p>
                    </div>

                    <!-- Plan Type -->
                    <div>
                        <label for="plan_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Plan Type <span class="text-red-500">*</span>
                        </label>
                        <select name="plan_type" 
                                id="plan_type" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('plan_type') border-red-500 @enderror">
                            <option value="single" {{ old('plan_type', $license->plan_type) == 'single' ? 'selected' : '' }}>
                                Single Site ($59/year)
                            </option>
                            <option value="triple" {{ old('plan_type', $license->plan_type) == 'triple' ? 'selected' : '' }}>
                                3 Sites ($99/year)
                            </option>
                            <option value="ten" {{ old('plan_type', $license->plan_type) == 'ten' ? 'selected' : '' }}>
                                10 Sites ($199/year)
                            </option>
                        </select>
                        @if($license->activations->count() > 0)
                            <p class="mt-1 text-sm text-orange-600">
                                ⚠️ This license has {{ $license->activations->count() }} active site(s). Downgrading may cause issues.
                            </p>
                        @endif
                        @error('plan_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" 
                                id="status" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                            <option value="active" {{ old('status', $license->status) == 'active' ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="suspended" {{ old('status', $license->status) == 'suspended' ? 'selected' : '' }}>
                                Suspended
                            </option>
                        </select>
                        @if($license->status == 'active' && old('status') != 'suspended')
                            <p class="mt-1 text-sm text-gray-500">
                                Suspending will prevent the license from being used
                            </p>
                        @endif
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Expiry Date -->
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                            Expiry Date
                        </label>
                        <input type="date" 
                               name="expires_at" 
                               id="expires_at" 
                               value="{{ old('expires_at', $license->expires_at?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Leave blank for lifetime license</p>
                        @if($license->expires_at)
                            @php
                                $daysUntilExpiry = now()->diffInDays($license->expires_at, false);
                            @endphp
                            @if($daysUntilExpiry < 0)
                                <p class="mt-1 text-sm text-red-600">
                                    Currently expired {{ abs($daysUntilExpiry) }} days ago
                                </p>
                            @elseif($daysUntilExpiry <= 30)
                                <p class="mt-1 text-sm text-orange-600">
                                    Expires in {{ $daysUntilExpiry }} days
                                </p>
                            @endif
                        @endif
                        @error('expires_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-between items-center">
                    <div>
                        <form method="POST" 
                              action="{{ route('admin.licenses.destroy', $license) }}"
                              onsubmit="return confirm('Are you sure you want to delete this license? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Delete License
                            </button>
                        </form>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.licenses.show', $license) }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update License
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-2">
                @if($license->expires_at)
                    <form method="POST" action="{{ route('admin.licenses.extend', $license) }}">
                        @csrf
                        <button type="submit" 
                                class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                            Extend by 1 Year
                        </button>
                    </form>
                @endif
                
                @if($license->status === 'active')
                    <form method="POST" 
                          action="{{ route('admin.licenses.revoke', $license) }}"
                          onsubmit="return confirm('Are you sure you want to revoke this license?');">
                        @csrf
                        <button type="submit" 
                                class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                            Revoke License
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
