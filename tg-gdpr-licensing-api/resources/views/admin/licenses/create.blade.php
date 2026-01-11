@extends('layouts.admin')

@section('title', 'Create License')
@section('page-title', 'Create License')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.licenses.store') }}">
                @csrf

                <div class="space-y-6">
                    <!-- Customer -->
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" 
                                id="customer_id" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('customer_id') border-red-500 @enderror">
                            <option value="">Select a customer...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            <option value="">Select a plan...</option>
                            <option value="single" {{ old('plan_type') == 'single' ? 'selected' : '' }}>
                                Single Site ($59/year)
                            </option>
                            <option value="triple" {{ old('plan_type') == 'triple' ? 'selected' : '' }}>
                                3 Sites ($99/year)
                            </option>
                            <option value="ten" {{ old('plan_type') == 'ten' ? 'selected' : '' }}>
                                10 Sites ($199/year)
                            </option>
                        </select>
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
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>
                                Suspended
                            </option>
                        </select>
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
                               value="{{ old('expires_at', now()->addYear()->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Leave blank for lifetime license</p>
                        @error('expires_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.licenses.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create License
                    </button>
                </div>
            </form>
        </div>

        <!-- Pricing Info -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">Pricing Guide</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• Single Site: $59/year (1 activation)</li>
                <li>• 3 Sites: $99/year (3 activations)</li>
                <li>• 10 Sites: $199/year (10 activations)</li>
            </ul>
        </div>
    </div>
@endsection
