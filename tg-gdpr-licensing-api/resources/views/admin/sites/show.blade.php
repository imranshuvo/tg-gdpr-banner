@extends('layouts.admin')

@section('title', 'Site: ' . $site->domain)
@section('page-title', $site->domain)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <nav class="text-sm mb-2">
                <a href="{{ route('admin.sites.index') }}" class="text-blue-600 hover:text-blue-800">Sites</a>
                <span class="text-gray-400 mx-2">/</span>
                <span class="text-gray-700">{{ $site->domain }}</span>
            </nav>
            <h2 class="text-lg font-semibold text-gray-900">{{ $site->domain }}</h2>
            <p class="text-sm text-gray-500">{{ $site->site_name ?? $site->site_url }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.sites.settings', $site) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-cog mr-2"></i> Settings
            </a>
            <a href="{{ route('admin.sites.edit', $site) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md">
                <i class="fas fa-pencil-alt mr-2"></i> Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-lg p-5 bg-blue-600 text-white">
                    <div class="text-xs opacity-75 uppercase tracking-wider">Sessions this month</div>
                    <div class="mt-1 text-3xl font-bold">{{ number_format($currentMonthSessions) }}</div>
                    <div class="text-xs opacity-80 mt-1">of {{ number_format($sessionLimit) }} limit</div>
                </div>
                <div class="rounded-lg p-5 bg-green-600 text-white">
                    <div class="text-xs opacity-75 uppercase tracking-wider">Accept all</div>
                    <div class="mt-1 text-3xl font-bold">{{ $consentStats['accept_all'] ?? 0 }}</div>
                    <div class="text-xs opacity-80 mt-1">last 30 days</div>
                </div>
                <div class="rounded-lg p-5 bg-amber-500 text-white">
                    <div class="text-xs opacity-90 uppercase tracking-wider">Customized</div>
                    <div class="mt-1 text-3xl font-bold">{{ $consentStats['customize'] ?? 0 }}</div>
                    <div class="text-xs opacity-80 mt-1">last 30 days</div>
                </div>
                <div class="rounded-lg p-5 bg-red-600 text-white">
                    <div class="text-xs opacity-75 uppercase tracking-wider">Reject all</div>
                    <div class="mt-1 text-3xl font-bold">{{ $consentStats['reject_all'] ?? 0 }}</div>
                    <div class="text-xs opacity-80 mt-1">last 30 days</div>
                </div>
            </div>

            {{-- Site token --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Site token</h3>
                    <form method="POST" action="{{ route('admin.sites.regenerate-token', $site) }}" onsubmit="return confirm('Regenerate token? The site integration will need to be updated.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-yellow-300 text-yellow-700 hover:bg-yellow-50">
                            <i class="fas fa-redo mr-1.5"></i> Regenerate
                        </button>
                    </form>
                </div>
                <div class="p-6">
                    <div class="flex">
                        <input type="text" id="siteToken" value="{{ $site->site_token }}" readonly
                               class="flex-1 rounded-l-md border-gray-300 bg-gray-50 font-mono text-sm">
                        <button type="button" onclick="copyToken()" class="inline-flex items-center px-4 py-2 bg-white border border-l-0 border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-r-md">
                            <i class="fas fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Use this token in the WordPress plugin's site integration settings.</p>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Quick actions</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <a href="{{ route('admin.sites.cookies', $site) }}" class="flex flex-col items-center justify-center px-4 py-5 border border-gray-200 rounded-md hover:bg-blue-50 hover:border-blue-300 text-center">
                        <i class="fas fa-cookie-bite text-2xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">View cookies</span>
                        <span class="text-xs text-gray-500 mt-0.5">{{ $site->cookies_detected }} detected</span>
                    </a>
                    <a href="{{ route('admin.sites.consents', $site) }}" class="flex flex-col items-center justify-center px-4 py-5 border border-gray-200 rounded-md hover:bg-green-50 hover:border-green-300 text-center">
                        <i class="fas fa-check-circle text-2xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">View consents</span>
                    </a>
                    <a href="{{ route('admin.sites.analytics', $site) }}" class="flex flex-col items-center justify-center px-4 py-5 border border-gray-200 rounded-md hover:bg-indigo-50 hover:border-indigo-300 text-center">
                        <i class="fas fa-chart-line text-2xl text-indigo-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Analytics</span>
                    </a>
                </div>
            </div>

            {{-- Policy version --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Policy version</h3>
                    <form method="POST" action="{{ route('admin.sites.increment-policy', $site) }}" onsubmit="return confirm('Increment policy version? This will force all visitors to re-consent.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-red-300 text-red-700 hover:bg-red-50">
                            <i class="fas fa-plus mr-1.5"></i> Force re-consent
                        </button>
                    </form>
                </div>
                <div class="p-6 flex items-center gap-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">v{{ $site->policy_version }}</span>
                    <span class="text-sm text-gray-700">
                        @if ($site->policy_updated_at)
                            Last updated: {{ $site->policy_updated_at->format('M j, Y H:i') }}
                        @else
                            Never updated
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Site details</h3>
                </div>
                <dl class="divide-y divide-gray-100">
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</dt>
                        <dd class="mt-1">
                            @switch($site->status)
                                @case('active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @break
                                @case('trial')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Trial</span>
                                    @if ($site->trial_ends_at)<div class="text-xs text-gray-500 mt-1">Ends {{ $site->trial_ends_at->format('M j, Y') }}</div>@endif
                                    @break
                                @case('paused')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Paused</span>
                                    @break
                                @case('expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</dt>
                        <dd class="mt-1 text-sm"><a href="{{ route('admin.customers.show', $site->customer) }}" class="text-blue-600 hover:text-blue-800">{{ $site->customer->name }}</a></dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">License</dt>
                        <dd class="mt-1 text-sm">
                            @if ($site->license)
                                <a href="{{ route('admin.licenses.show', $site->license) }}" class="text-blue-600 hover:text-blue-800 font-mono text-xs">{{ $site->license->license_key }}</a>
                                <div class="text-xs text-gray-500 mt-0.5">{{ ucfirst($site->license->plan) }} plan</div>
                            @else
                                <span class="text-gray-400">No license</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Domain</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $site->domain }}</dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Site URL</dt>
                        <dd class="mt-1 text-sm"><a href="{{ $site->site_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 break-all">{{ $site->site_url }} <i class="fas fa-external-link-alt text-xs"></i></a></dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $site->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Features</h3>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach ([
                        ['TCF 2.2', $site->tcf_enabled],
                        ['Google Consent Mode', $site->gcm_enabled],
                        ['Geo Targeting', $site->geo_targeting_enabled],
                    ] as [$label, $enabled])
                        <li class="px-6 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-900">{{ $label }}</span>
                            @if ($enabled)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Disabled</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-red-200">
                <div class="px-6 py-3 bg-red-600 text-white rounded-t-lg">
                    <h3 class="text-base font-semibold">Danger zone</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" onsubmit="return confirm('Delete this site? This will erase all consent records and settings for it.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-red-300 hover:bg-red-50 text-red-700 text-sm font-medium rounded-md">
                            <i class="fas fa-trash mr-2"></i> Delete site
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
    navigator.clipboard?.writeText(input.value) || document.execCommand('copy');
}
</script>
@endsection
