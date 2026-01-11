@extends('layouts.admin')

@section('title', 'Add New Site')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Add New Site</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.sites.store') }}">
                @csrf

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Site Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">License (Optional)</label>
                                <select name="license_id" class="form-select @error('license_id') is-invalid @enderror">
                                    <option value="">No License (Trial)</option>
                                    @foreach($licenses as $license)
                                        <option value="{{ $license->id }}" {{ old('license_id') == $license->id ? 'selected' : '' }}>
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
                                       value="{{ old('site_url') }}" required placeholder="https://example.com">
                                @error('site_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Domain <span class="text-danger">*</span></label>
                                <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" 
                                       value="{{ old('domain') }}" required placeholder="example.com">
                                <small class="text-muted">Will be auto-extracted from URL if left empty</small>
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror" 
                                       value="{{ old('site_name') }}" placeholder="My Website">
                                @error('site_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="trial" {{ old('status', 'trial') == 'trial' ? 'selected' : '' }}>Trial (30 days)</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="paused" {{ old('status') == 'paused' ? 'selected' : '' }}>Paused</option>
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
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tcf_enabled" value="1" id="tcf_enabled"
                                           {{ old('tcf_enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tcf_enabled">
                                        <strong>TCF 2.2</strong>
                                        <br><small class="text-muted">IAB Transparency & Consent Framework</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="gcm_enabled" value="1" id="gcm_enabled"
                                           {{ old('gcm_enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gcm_enabled">
                                        <strong>Google Consent Mode</strong>
                                        <br><small class="text-muted">Required for Google Ads in EU</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="geo_targeting_enabled" value="1" id="geo_targeting_enabled"
                                           {{ old('geo_targeting_enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="geo_targeting_enabled">
                                        <strong>Geo Targeting</strong>
                                        <br><small class="text-muted">Show only to EU visitors</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Create Site
                    </button>
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
