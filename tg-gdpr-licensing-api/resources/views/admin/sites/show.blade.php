@extends('layouts.admin')

@section('title', 'Site: ' . $site->domain)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item active">{{ $site->domain }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $site->domain }}</h1>
            <p class="text-muted mb-0">{{ $site->site_name ?? $site->site_url }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.sites.settings', $site) }}" class="btn btn-primary">
                <i class="bi bi-gear me-1"></i> Settings
            </a>
            <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Main Info --}}
        <div class="col-lg-8">
            {{-- Stats Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 opacity-75">Sessions This Month</h6>
                            <h3 class="card-title mb-0">{{ number_format($currentMonthSessions) }}</h3>
                            <small>of {{ number_format($sessionLimit) }} limit</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 opacity-75">Accept All</h6>
                            <h3 class="card-title mb-0">{{ $consentStats['accept_all'] ?? 0 }}</h3>
                            <small>last 30 days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 opacity-75">Customized</h6>
                            <h3 class="card-title mb-0">{{ $consentStats['customize'] ?? 0 }}</h3>
                            <small>last 30 days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 opacity-75">Reject All</h6>
                            <h3 class="card-title mb-0">{{ $consentStats['reject_all'] ?? 0 }}</h3>
                            <small>last 30 days</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Site Token --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Site Token</h5>
                    <form method="POST" action="{{ route('admin.sites.regenerate-token', $site) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Regenerate token? The site integration will need to be updated.')">
                            <i class="bi bi-arrow-clockwise me-1"></i> Regenerate
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" value="{{ $site->site_token }}" readonly id="siteToken">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <small class="text-muted">Use this token in your site integration settings</small>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('admin.sites.cookies', $site) }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-cookie d-block mb-1" style="font-size: 1.5rem;"></i>
                                View Cookies ({{ $site->cookies_detected }})
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.sites.consents', $site) }}" class="btn btn-outline-success w-100">
                                <i class="bi bi-check-circle d-block mb-1" style="font-size: 1.5rem;"></i>
                                View Consents
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.sites.analytics', $site) }}" class="btn btn-outline-info w-100">
                                <i class="bi bi-graph-up d-block mb-1" style="font-size: 1.5rem;"></i>
                                Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Policy Version --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Policy Version</h5>
                    <form method="POST" action="{{ route('admin.sites.increment-policy', $site) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Increment policy version? This will force all visitors to re-consent.')">
                            <i class="bi bi-plus-lg me-1"></i> Force Re-consent
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary fs-5 me-3">v{{ $site->policy_version }}</span>
                        <div>
                            @if($site->policy_updated_at)
                                Last updated: {{ $site->policy_updated_at->format('M j, Y H:i') }}
                            @else
                                Never updated
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Site Details</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Status</dt>
                        <dd>
                            @switch($site->status)
                                @case('active')
                                    <span class="badge bg-success">Active</span>
                                    @break
                                @case('trial')
                                    <span class="badge bg-info">Trial</span>
                                    @if($site->trial_ends_at)
                                        <br><small class="text-muted">Ends {{ $site->trial_ends_at->format('M j, Y') }}</small>
                                    @endif
                                    @break
                                @case('paused')
                                    <span class="badge bg-warning">Paused</span>
                                    @break
                                @case('expired')
                                    <span class="badge bg-danger">Expired</span>
                                    @break
                            @endswitch
                        </dd>

                        <dt>Customer</dt>
                        <dd>
                            <a href="{{ route('admin.customers.show', $site->customer) }}">
                                {{ $site->customer->name }}
                            </a>
                        </dd>

                        <dt>License</dt>
                        <dd>
                            @if($site->license)
                                <a href="{{ route('admin.licenses.show', $site->license) }}">
                                    {{ $site->license->license_key }}
                                </a>
                                <br><small class="text-muted">{{ ucfirst($site->license->plan) }} plan</small>
                            @else
                                <span class="text-muted">No license</span>
                            @endif
                        </dd>

                        <dt>Domain</dt>
                        <dd>{{ $site->domain }}</dd>

                        <dt>Site URL</dt>
                        <dd>
                            <a href="{{ $site->site_url }}" target="_blank">
                                {{ $site->site_url }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </dd>

                        <dt>Created</dt>
                        <dd>{{ $site->created_at->format('M j, Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Features Enabled --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Features</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        TCF 2.2
                        @if($site->tcf_enabled)
                            <span class="badge bg-success">Enabled</span>
                        @else
                            <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Google Consent Mode
                        @if($site->gcm_enabled)
                            <span class="badge bg-success">Enabled</span>
                        @else
                            <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Geo Targeting
                        @if($site->geo_targeting_enabled)
                            <span class="badge bg-success">Enabled</span>
                        @else
                            <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </li>
                </ul>
            </div>

            {{-- Danger Zone --}}
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Danger Zone</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" onsubmit="return confirm('Are you sure? This will delete all consent records and settings for this site.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i> Delete Site
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToken() {
    const input = document.getElementById('siteToken');
    input.select();
    document.execCommand('copy');
    alert('Token copied to clipboard!');
}
</script>
@endsection
