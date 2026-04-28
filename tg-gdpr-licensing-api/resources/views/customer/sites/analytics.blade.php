<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Analytics — {{ $site->site_name ?: $site->domain }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $site->domain }}</p>
            </div>
            <div class="flex items-center gap-2">
                @foreach ([7, 30, 90, 365] as $p)
                    <a href="{{ route('customer.sites.analytics', ['site' => $site, 'period' => $p]) }}"
                       class="px-3 py-1.5 text-sm rounded-md border {{ $period === $p ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $p === 365 ? '1y' : $p . 'd' }}
                    </a>
                @endforeach
                <a href="{{ route('customer.sites.show', $site) }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">← Back</a>
            </div>
        </div>
    </x-slot>

    @php
        $maxSession = max($analytics['sessions_data']) ?: 1;
        $topCountryMax = !empty($analytics['top_countries']) ? max($analytics['top_countries']) : 1;
    @endphp

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($analytics['total_sessions']) }}</dd>
                @if ($analytics['session_change'] != 0.0)
                    <dd class="text-xs mt-1 {{ $analytics['session_change'] > 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ $analytics['session_change'] > 0 ? '↑' : '↓' }} {{ abs($analytics['session_change']) }}% vs prior period
                    </dd>
                @endif
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total consents</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($analytics['total_consents']) }}</dd>
                @if ($analytics['has_banner_data'] && $analytics['consent_rate'] !== null)
                    <dd class="text-xs mt-1 text-gray-500">{{ $analytics['consent_rate'] }}% interaction rate</dd>
                @endif
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Accept-all rate</dt>
                <dd class="mt-1 text-3xl font-bold text-green-700">{{ $analytics['accept_all_rate'] }}%</dd>
                <dd class="text-xs mt-1 text-gray-500">{{ number_format($analytics['accept_all_count']) }} of {{ number_format($analytics['total_consents']) }}</dd>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Monthly usage</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $analytics['usage_percentage'] }}%</dd>
                <dd class="text-xs mt-1 text-gray-500">{{ number_format($analytics['sessions_used']) }} / {{ number_format($analytics['sessions_limit']) }}</dd>
            </div>
        </div>

        {{-- Daily session bars --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Daily sessions ({{ $period }} days)</h3>
            @if ($analytics['total_sessions'] === 0)
                <p class="text-sm text-gray-500">No sessions recorded for this period.</p>
            @else
                <div class="flex items-end gap-px h-40 overflow-x-auto">
                    @foreach ($analytics['sessions_data'] as $i => $count)
                        @php $h = max(2, round(($count / $maxSession) * 100)); @endphp
                        <div class="flex-1 min-w-[8px] group relative" title="{{ $analytics['sessions_labels'][$i] }}: {{ number_format($count) }}">
                            <div class="bg-indigo-500 hover:bg-indigo-600 transition-colors rounded-t" style="height: {{ $h }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-400">
                    <span>{{ $analytics['sessions_labels'][0] }}</span>
                    <span>{{ end($analytics['sessions_labels']) }}</span>
                </div>
            @endif
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            {{-- Consent split --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Consent split</h3>
                @php
                    $total = $analytics['total_consents'] ?: 1;
                    $rows = [
                        ['Accept all',  $analytics['accept_all_count'],     'bg-green-500'],
                        ['Customize',   $analytics['custom_count'],         'bg-blue-500'],
                        ['Reject all',  $analytics['reject_all_count'],     'bg-red-500'],
                        ['No action',   $analytics['no_interaction_count'], 'bg-gray-400'],
                    ];
                @endphp
                <div class="space-y-3">
                    @foreach ($rows as [$label, $count, $color])
                        @php $pct = round(($count / $total) * 100, 1); @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-700 font-medium">{{ $label }}</span>
                                <span class="text-gray-500">{{ number_format($count) }} ({{ $pct }}%)</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded overflow-hidden">
                                <div class="{{ $color }} h-full" style="width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Per-category acceptance --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Per-category acceptance</h3>
                <div class="space-y-3">
                    @foreach ($analytics['category_rates'] as $category => $rate)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-700 font-medium capitalize">{{ $category }}</span>
                                <span class="text-gray-500">{{ $rate }}%</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded overflow-hidden">
                                <div class="bg-indigo-500 h-full" style="width: {{ $rate }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            {{-- Top countries --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Top countries</h3>
                @if (empty($analytics['top_countries']))
                    <p class="text-sm text-gray-500">No country data yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($analytics['top_countries'] as $country => $count)
                            @php $pct = round(($count / $topCountryMax) * 100, 1); @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-700 font-medium">{{ $country }}</span>
                                    <span class="text-gray-500">{{ number_format($count) }}</span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded overflow-hidden">
                                    <div class="bg-violet-500 h-full" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- GCM v2 signal granted-rate --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Google Consent Mode v2 — granted rate</h3>
                @php $hasGcm = collect($analytics['gcm_stats'])->some(fn ($s) => $s['total'] > 0); @endphp
                @if (! $hasGcm)
                    <p class="text-sm text-gray-500">No GCM signals recorded yet. Once visitors interact with the banner on a Google-tag-enabled site, signals appear here.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($analytics['gcm_stats'] as $signal => $stats)
                            @php $rate = $stats['total'] > 0 ? round(($stats['granted'] / $stats['total']) * 100, 1) : 0; @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-700 font-mono">{{ $signal }}</span>
                                    <span class="text-gray-500">{{ $rate }}% ({{ $stats['granted'] }}/{{ $stats['total'] }})</span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded overflow-hidden">
                                    <div class="bg-sky-500 h-full" style="width: {{ $rate }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent consent records --}}
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Most recent consents</h3>
            </div>
            @if ($recentConsents->isEmpty())
                <div class="p-6 text-sm text-gray-500">No consents in this period.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">When</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Country</th>
                            <th class="px-6 py-3">Device</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($recentConsents as $c)
                            <tr>
                                <td class="px-6 py-4 text-gray-600">{{ $c->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $c->consent_method === 'accept_all' ? 'bg-green-100 text-green-800' : ($c->consent_method === 'reject_all' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ str_replace('_', ' ', $c->consent_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $c->country_code ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $c->device_type ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
