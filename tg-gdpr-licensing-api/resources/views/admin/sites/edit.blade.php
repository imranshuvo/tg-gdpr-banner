@extends('layouts.admin')

@section('title', 'Edit Site: ' . $site->domain)
@section('page-title', 'Edit ' . $site->domain)

@section('content')
@php
    $geoMode = old('geo_targeting_mode', $site->getGeoTargetingMode());
    $selectedGeoCountries = old('geo_countries', $site->getGeoTargetingMode() === 'selected' ? ($site->geo_countries ?? []) : []);
    $availableGeoCountries = [
        'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
        'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
        'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
        'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
        'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
        'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
        'SI' => 'Slovenia', 'ES' => 'Spain', 'SE' => 'Sweden', 'GB' => 'United Kingdom',
        'IS' => 'Iceland', 'LI' => 'Liechtenstein', 'NO' => 'Norway', 'CH' => 'Switzerland',
    ];
@endphp

<div class="max-w-4xl space-y-6">
    <nav class="text-sm">
        <a href="{{ route('admin.sites.index') }}" class="text-blue-600 hover:text-blue-800">Sites</a>
        <span class="text-gray-400 mx-2">/</span>
        <a href="{{ route('admin.sites.show', $site) }}" class="text-blue-600 hover:text-blue-800">{{ $site->domain }}</a>
        <span class="text-gray-400 mx-2">/</span>
        <span class="text-gray-700">Edit</span>
    </nav>

    <form method="POST" action="{{ route('admin.sites.update', $site) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Site information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                    <select name="customer_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('customer_id') border-red-500 @enderror">
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $site->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">License</label>
                    <select name="license_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('license_id') border-red-500 @enderror">
                        <option value="">No license</option>
                        @foreach ($licenses as $license)
                            <option value="{{ $license->id }}" {{ old('license_id', $site->license_id) == $license->id ? 'selected' : '' }}>
                                {{ $license->license_key }} ({{ ucfirst($license->plan) }})
                            </option>
                        @endforeach
                    </select>
                    @error('license_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site URL <span class="text-red-500">*</span></label>
                    <input type="url" name="site_url" value="{{ old('site_url', $site->site_url) }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('site_url') border-red-500 @enderror">
                    @error('site_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Domain <span class="text-red-500">*</span></label>
                    <input type="text" name="domain" value="{{ old('domain', $site->domain) }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('domain') border-red-500 @enderror">
                    @error('domain')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site name</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $site->site_name) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach (['active', 'trial', 'paused', 'expired', 'deleted'] as $s)
                            <option value="{{ $s }}" {{ old('status', $site->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Features</h3>
            </div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="tcf_enabled" value="1" {{ old('tcf_enabled', $site->tcf_enabled) ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div><div class="text-sm font-semibold text-gray-900">TCF 2.2</div></div>
                    </label>
                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="gcm_enabled" value="1" {{ old('gcm_enabled', $site->gcm_enabled) ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div><div class="text-sm font-semibold text-gray-900">Google Consent Mode v2</div></div>
                    </label>
                </div>

                <div x-data="{ mode: '{{ $geoMode }}' }" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Geo scope</label>
                        <select name="geo_targeting_mode" x-model="mode"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('geo_targeting_mode') border-red-500 @enderror">
                            <option value="all">All countries</option>
                            <option value="eu">EU/EEA/UK/CH only</option>
                            <option value="selected">Selected European countries only</option>
                        </select>
                    </div>

                    <div x-show="mode === 'selected'" x-cloak x-transition>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Selected countries</label>
                        <select name="geo_countries[]" multiple size="10"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('geo_countries') border-red-500 @enderror">
                            @foreach ($availableGeoCountries as $code => $label)
                                <option value="{{ $code }}" {{ in_array($code, $selectedGeoCountries, true) ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Cmd on Mac) to multi-select.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-check mr-2"></i> Save changes
            </button>
            <a href="{{ route('admin.sites.show', $site) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
</div>
@endsection
