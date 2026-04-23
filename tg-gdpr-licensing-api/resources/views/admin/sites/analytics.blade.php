@extends('layouts.admin')

@section('title', 'Analytics - ' . $site->domain)

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.sites.index') }}" class="text-gray-400 hover:text-gray-500">Sites</a>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <a href="{{ route('admin.sites.show', $site) }}" class="ml-2 text-gray-400 hover:text-gray-500">{{ $site->domain }}</a>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-2 text-gray-500">Analytics</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
            </div>
            
            <form method="GET" class="flex items-center space-x-4">
                <select name="period" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="7" {{ (int) $period === 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ (int) $period === 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ (int) $period === 90 ? 'selected' : '' }}>Last 90 days</option>
                    <option value="365" {{ (int) $period === 365 ? 'selected' : '' }}>Last year</option>
                </select>
            </form>
        </div>
@extends('layouts.admin')

@section('title', 'Analytics - ' . $site->domain)

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.sites.index') }}" class="text-gray-400 hover:text-gray-500">Sites</a>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <a href="{{ route('admin.sites.show', $site) }}" class="ml-2 text-gray-400 hover:text-gray-500">{{ $site->domain }}</a>
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-2 text-gray-500">Analytics</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
            </div>

            <form method="GET" class="flex items-center space-x-4">
                <select name="period" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="7" {{ (int) $period === 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ (int) $period === 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ (int) $period === 90 ? 'selected' : '' }}>Last 90 days</option>
                    <option value="365" {{ (int) $period === 365 ? 'selected' : '' }}>Last year</option>
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Sessions</h3>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($analytics['total_sessions'] ?? 0) }}</p>
                        @if(isset($analytics['session_change']))
                            <p class="text-sm {{ $analytics['session_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $analytics['session_change'] >= 0 ? '+' : '' }}{{ $analytics['session_change'] }}% vs previous period
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Consent Rate</h3>
                        @if(($analytics['consent_rate'] ?? null) !== null)
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($analytics['consent_rate'], 1) }}%</p>
                            <p class="text-sm text-gray-500">{{ number_format($analytics['total_consents'] ?? 0) }} total consents</p>
                        @else
                            <p class="text-2xl font-semibold text-gray-400">--</p>
                            <p class="text-sm text-gray-500">Awaiting banner impressions</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Accept All Rate</h3>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($analytics['accept_all_rate'] ?? 0, 1) }}%</p>
                        <p class="text-sm text-gray-500">{{ number_format($analytics['accept_all_count'] ?? 0) }} users</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Plan Usage</h3>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($analytics['usage_percentage'] ?? 0, 0) }}%</p>
                        <p class="text-sm text-gray-500">
                            {{ number_format($analytics['sessions_used'] ?? 0) }} / {{ number_format($analytics['sessions_limit'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @php
            $sessionsData = array_values($analytics['sessions_data'] ?? []);
            $sessionsLabels = array_values($analytics['sessions_labels'] ?? []);
            $sessionPointCount = count($sessionsData);
            $sessionMax = max($sessionsData ?: [0]);
            $sessionPoints = [];

            foreach ($sessionsData as $index => $value) {
                $x = $sessionPointCount > 1 ? round(($index / ($sessionPointCount - 1)) * 100, 2) : 50;
                $y = $sessionMax > 0 ? round(100 - (($value / $sessionMax) * 88), 2) : 100;
                $sessionPoints[] = $x . ',' . $y;
            }

            $sessionLinePoints = implode(' ', $sessionPoints);
            $consentBreakdown = [
                ['label' => 'Accept All', 'count' => $analytics['accept_all_count'] ?? 0, 'bar_class' => 'bg-green-500'],
                ['label' => 'Reject All', 'count' => $analytics['reject_all_count'] ?? 0, 'bar_class' => 'bg-red-500'],
                ['label' => 'Custom', 'count' => $analytics['custom_count'] ?? 0, 'bar_class' => 'bg-blue-500'],
                ['label' => 'No Interaction', 'count' => $analytics['no_interaction_count'] ?? 0, 'bar_class' => 'bg-gray-400'],
            ];
            $consentBreakdownTotal = array_sum(array_column($consentBreakdown, 'count'));
            $midpointIndex = $sessionPointCount > 0 ? (int) floor(($sessionPointCount - 1) / 2) : 0;
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sessions Over Time</h3>
                @if($sessionPointCount > 0 && $sessionMax > 0)
                    <div class="h-64 rounded-lg bg-gradient-to-b from-indigo-50 to-white p-4">
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                            <line x1="0" y1="100" x2="100" y2="100" stroke="#d1d5db" stroke-width="0.8"></line>
                            <line x1="0" y1="66" x2="100" y2="66" stroke="#e5e7eb" stroke-width="0.6" stroke-dasharray="2 2"></line>
                            <line x1="0" y1="33" x2="100" y2="33" stroke="#e5e7eb" stroke-width="0.6" stroke-dasharray="2 2"></line>
                            <polyline points="{{ $sessionLinePoints }}" fill="none" stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        </svg>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $sessionsLabels[0] ?? '' }}</span>
                        <span>{{ $sessionsLabels[$midpointIndex] ?? '' }}</span>
                        <span>{{ $sessionsLabels[$sessionPointCount - 1] ?? '' }}</span>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center rounded-lg border border-dashed border-gray-200 text-sm text-gray-500">
                        No session trend data recorded for this period.
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Consent Breakdown</h3>
                <div class="space-y-4">
                    <div class="flex h-4 overflow-hidden rounded-full bg-gray-100">
                        @foreach($consentBreakdown as $segment)
                            @php
                                $segmentShare = $consentBreakdownTotal > 0 ? ($segment['count'] / $consentBreakdownTotal) * 100 : 0;
                            @endphp
                            @if($segmentShare > 0)
                                <div class="{{ $segment['bar_class'] }}" style="width: {{ $segmentShare }}%"></div>
                            @endif
                        @endforeach
                    </div>
                    @foreach($consentBreakdown as $segment)
                        @php
                            $segmentShare = $consentBreakdownTotal > 0 ? round(($segment['count'] / $consentBreakdownTotal) * 100, 1) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $segment['label'] }}</span>
                                <span class="text-gray-500">{{ number_format($segment['count']) }} ({{ number_format($segmentShare, 1) }}%)</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100">
                                <div class="h-2.5 rounded-full {{ $segment['bar_class'] }}" style="width: {{ $segmentShare }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Category Consent Rates</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach(['necessary' => 'Necessary', 'functional' => 'Functional', 'analytics' => 'Analytics', 'marketing' => 'Marketing'] as $key => $label)
                        @php
                            $rate = $analytics['category_rates'][$key] ?? ($key === 'necessary' ? 100 : 0);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($rate, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full {{ $key === 'necessary' ? 'bg-gray-600' : ($key === 'marketing' ? 'bg-purple-600' : ($key === 'analytics' ? 'bg-blue-600' : 'bg-green-600')) }}" style="width: {{ $rate }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Google Consent Mode v2 Stats</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach(['ad_storage' => 'Ad Storage', 'analytics_storage' => 'Analytics Storage', 'ad_user_data' => 'Ad User Data', 'ad_personalization' => 'Ad Personalization'] as $key => $label)
                        @php
                            $granted = $analytics['gcm_stats'][$key]['granted'] ?? 0;
                            $total = $analytics['gcm_stats'][$key]['total'] ?? 1;
                            $rate = $total > 0 ? ($granted / $total * 100) : 0;
                        @endphp
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-2">
                                <span class="text-xl font-bold {{ $rate > 50 ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ number_format($rate, 0) }}%
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($granted) }} granted</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Top Countries</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($analytics['top_countries'] ?? [] as $countryCode => $count)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $countryCode }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($count) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-indigo-600" style="width: {{ max(5, round(($count / max(1, array_sum($analytics['top_countries'] ?? []))) * 100, 1)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No country breakdown recorded for this period.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Device Mix</h3>
                </div>
                <div class="p-6 space-y-4">
                    @php
                        $deviceTotal = array_sum($analytics['device_breakdown'] ?? []);
                    @endphp
                    @forelse($analytics['device_breakdown'] ?? [] as $deviceType => $count)
                        @php
                            $share = $deviceTotal > 0 ? round(($count / $deviceTotal) * 100, 1) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 capitalize">{{ $deviceType }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($share, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-green-600" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No device breakdown recorded for this period.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Recent Consent Activity</h3>
                <a href="{{ route('admin.sites.consents', $site) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                    View all →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interaction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentConsents ?? [] as $consent)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $consent->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $badgeClass = match($consent->consent_method) {
                                            'accept_all' => 'bg-green-100 text-green-800',
                                            'reject_all' => 'bg-red-100 text-red-800',
                                            'customize' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $badgeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $consent->consent_method ?? 'unknown')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php
                                        $categories = [];
                                        $consentData = $consent->consent_categories;

                                        if (is_string($consentData)) {
                                            $consentData = json_decode($consentData, true);
                                        }

                                        if ($consentData['necessary'] ?? false) $categories[] = 'N';
                                        if ($consentData['functional'] ?? false) $categories[] = 'F';
                                        if ($consentData['analytics'] ?? false) $categories[] = 'A';
                                        if ($consentData['marketing'] ?? false) $categories[] = 'M';
                                    @endphp
                                    {{ implode(', ', $categories) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($consent->device_type ?? 'unknown') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $consent->country_code ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No consent records yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
                        <div>
