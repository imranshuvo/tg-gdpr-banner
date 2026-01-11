@extends('layouts.admin')

@section('title', 'Sites Management')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Sites</h1>
            <p class="text-muted mb-0">Manage all CMP tenant sites</p>
        </div>
        <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Site
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Domain, name, URL..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Sites Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Site</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Sessions (This Month)</th>
                        <th>Last Scan</th>
                        <th>Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sites as $site)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $site->domain }}</div>
                                <small class="text-muted">{{ $site->site_name ?? $site->site_url }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.customers.show', $site->customer) }}">
                                    {{ $site->customer->name }}
                                </a>
                            </td>
                            <td>
                                @switch($site->status)
                                    @case('active')
                                        <span class="badge bg-success">Active</span>
                                        @break
                                    @case('trial')
                                        <span class="badge bg-info">
                                            Trial
                                            @if($site->trial_ends_at)
                                                ({{ $site->trial_ends_at->diffForHumans() }})
                                            @endif
                                        </span>
                                        @break
                                    @case('paused')
                                        <span class="badge bg-warning">Paused</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-danger">Expired</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $site->status }}</span>
                                @endswitch
                            </td>
                            <td>
                                @php
                                    $sessions = $site->getCurrentMonthSessions();
                                    $limit = $site->getSessionLimit();
                                    $percentage = $limit > 0 ? ($sessions / $limit) * 100 : 0;
                                @endphp
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px; width: 100px;">
                                        <div class="progress-bar {{ $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success') }}" 
                                             style="width: {{ min($percentage, 100) }}%"></div>
                                    </div>
                                    <small>{{ number_format($sessions) }} / {{ number_format($limit) }}</small>
                                </div>
                            </td>
                            <td>
                                @if($site->last_scan_at)
                                    {{ $site->last_scan_at->diffForHumans() }}
                                    <br><small class="text-muted">{{ $site->cookies_detected }} cookies</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>{{ $site->created_at->format('M j, Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.sites.settings', $site) }}" class="btn btn-outline-secondary" title="Settings">
                                        <i class="bi bi-gear"></i>
                                    </a>
                                    <a href="{{ route('admin.sites.analytics', $site) }}" class="btn btn-outline-info" title="Analytics">
                                        <i class="bi bi-graph-up"></i>
                                    </a>
                                    <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-globe2 display-4 d-block mb-3"></i>
                                No sites found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($sites->hasPages())
            <div class="card-footer">
                {{ $sites->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
