@extends('layouts.admin')

@section('title', 'Edit Site: ' . $site->domain)

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sites.show', $site) }}">{{ $site->domain }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Edit Site</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.sites.update', $site) }}">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Site Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $site->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">License</label>
                                <select name="license_id" class="form-select @error('license_id') is-invalid @enderror">
                                    <option value="">No License</option>
                                    @foreach($licenses as $license)
                                        <option value="{{ $license->id }}" {{ old('license_id', $site->license_id) == $license->id ? 'selected' : '' }}>
                                            {{ $license->license_key }} ({{ ucfirst($license->plan) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('license_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Site URL <span class="text-danger">*</span></label>
                                <input type="url" name="site_url" class="form-control @error('site_url') is-invalid @enderror" 
                                       value="{{ old('site_url', $site->site_url) }}" required>
                                @error('site_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Domain <span class="text-danger">*</span></label>
                                <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" 
                                       value="{{ old('domain', $site->domain) }}" required>
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror" 
                                       value="{{ old('site_name', $site->site_name) }}">
                                @error('site_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $site->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="trial" {{ old('status', $site->status) == 'trial' ? 'selected' : '' }}>Trial</option>
                                    <option value="paused" {{ old('status', $site->status) == 'paused' ? 'selected' : '' }}>Paused</option>
                                    <option value="expired" {{ old('status', $site->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="deleted" {{ old('status', $site->status) == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Features</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $geoMode = old('geo_targeting_mode', $site->getGeoTargetingMode());
                            $selectedGeoCountries = old('geo_countries', $site->getGeoTargetingMode() === 'selected' ? ($site->geo_countries ?? []) : []);
                            $availableGeoCountries = [
                                'AT' => 'Austria',
                                'BE' => 'Belgium',
                                'BG' => 'Bulgaria',
                                'HR' => 'Croatia',
                                'CY' => 'Cyprus',
                                'CZ' => 'Czech Republic',
                                'DK' => 'Denmark',
                                'EE' => 'Estonia',
                                'FI' => 'Finland',
                                'FR' => 'France',
                                'DE' => 'Germany',
                                'GR' => 'Greece',
                                'HU' => 'Hungary',
                                'IE' => 'Ireland',
                                'IT' => 'Italy',
                                'LV' => 'Latvia',
                                'LT' => 'Lithuania',
                                'LU' => 'Luxembourg',
                                'MT' => 'Malta',
                                'NL' => 'Netherlands',
                                'PL' => 'Poland',
                                'PT' => 'Portugal',
                                'RO' => 'Romania',
                                'SK' => 'Slovakia',
                                'SI' => 'Slovenia',
                                'ES' => 'Spain',
                                'SE' => 'Sweden',
                                'GB' => 'United Kingdom',
                                'IS' => 'Iceland',
                                'LI' => 'Liechtenstein',
                                'NO' => 'Norway',
                                'CH' => 'Switzerland',
                            ];
                        @endphp
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tcf_enabled" value="1" id="tcf_enabled"
                                           {{ old('tcf_enabled', $site->tcf_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tcf_enabled">
                                        <strong>TCF 2.2</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="gcm_enabled" value="1" id="gcm_enabled"
                                           {{ old('gcm_enabled', $site->gcm_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gcm_enabled">
                                        <strong>Google Consent Mode</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Geo Scope</label>
                                <select name="geo_targeting_mode" class="form-select @error('geo_targeting_mode') is-invalid @enderror">
                                    <option value="all" {{ $geoMode === 'all' ? 'selected' : '' }}>All countries</option>
                                    <option value="eu" {{ $geoMode === 'eu' ? 'selected' : '' }}>EU/EEA/UK/CH only</option>
                                    <option value="selected" {{ $geoMode === 'selected' ? 'selected' : '' }}>Selected European countries only</option>
                                </select>
                                @error('geo_targeting_mode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Controls where the CMP banner and regional consent defaults apply.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Selected Countries</label>
                                <select name="geo_countries[]" class="form-select @error('geo_countries') is-invalid @enderror @error('geo_countries.*') is-invalid @enderror" multiple size="10">
                                    @foreach($availableGeoCountries as $code => $label)
                                        <option value="{{ $code }}" {{ in_array($code, $selectedGeoCountries, true) ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                                @error('geo_countries')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('geo_countries.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only used when “Selected European countries only” is chosen.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
