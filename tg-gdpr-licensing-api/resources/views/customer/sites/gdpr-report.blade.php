<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">GDPR Compliance Report</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $site->site_name ?: $site->domain }} · {{ $site->domain }}</p>
            </div>
            <div class="flex items-center gap-2">
                @foreach (\App\Services\Compliance\GdprReportService::ALLOWED_PERIODS as $p)
                    <a href="{{ route('customer.sites.gdpr-report', ['site' => $site, 'period' => $p]) }}"
                       class="px-3 py-1.5 text-sm rounded-md border {{ $period === $p ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $p === 365 ? '1y' : $p . 'd' }}
                    </a>
                @endforeach
                <a href="{{ route('customer.sites.gdpr-report', ['site' => $site, 'period' => $period, 'format' => 'json']) }}"
                   class="ml-2 inline-flex items-center px-3 py-1.5 text-sm rounded-md bg-white border border-gray-300 hover:bg-gray-50 text-gray-700">
                    Download JSON
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center px-3 py-1.5 text-sm rounded-md bg-white border border-gray-300 hover:bg-gray-50 text-gray-700">Print / PDF</button>
                <a href="{{ route('customer.sites.show', $site) }}" class="ml-1 text-sm text-gray-600 hover:text-gray-900">← Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Header card --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Reporting period</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">
                        {{ \Carbon\Carbon::parse($report['period']['from'])->format('M j, Y') }}
                        — {{ \Carbon\Carbon::parse($report['period']['to'])->format('M j, Y') }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $report['period']['days'] }} days · generated {{ \Carbon\Carbon::parse($report['metadata']['generated_at'])->format('M j, Y · H:i T') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Site</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $report['site']['domain'] }}</p>
                    <p class="text-xs text-gray-500">Policy v{{ $report['site']['policy_version'] }} · {{ ucfirst($report['site']['status']) }}</p>
                </div>
            </div>
        </div>

        {{-- 1. Summary KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($report['summary']['sessions']) }}</dd>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total consents</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($report['summary']['total_consents']) }}</dd>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Consent rate</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $report['summary']['consent_rate_pct'] !== null ? $report['summary']['consent_rate_pct'] . '%' : '—' }}</dd>
                <dd class="text-xs text-gray-500 mt-1">Visitors who interacted with the banner</dd>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Accept-all rate</dt>
                <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $report['summary']['accept_all_pct'] }}%</dd>
                <dd class="text-xs text-gray-500 mt-1">Of those who interacted</dd>
            </div>
        </div>

        {{-- 2. Tamper evidence --}}
        @php $te = $report['tamper_evidence']; $teOk = $te['signatures_failed_verify'] === 0; @endphp
        <div class="bg-white rounded-lg shadow-sm border {{ $teOk ? 'border-emerald-200' : 'border-red-300' }}">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Tamper evidence (Article 7 — demonstrable consent)</h3>
                    <p class="text-sm text-gray-500">HMAC signature spot-check on the most-recent {{ $te['sample_size'] }} consents in this period.</p>
                </div>
                @if ($teOk)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">PASS</span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">FAIL</span>
                @endif
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Signed</dt><dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $te['records_signed'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Verified</dt><dd class="mt-1 text-2xl font-semibold text-emerald-700">{{ $te['signatures_verified'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Unsigned (legacy)</dt><dd class="mt-1 text-2xl font-semibold text-gray-500">{{ $te['records_unsigned_legacy'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Failed verify</dt><dd class="mt-1 text-2xl font-semibold {{ $te['signatures_failed_verify'] > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ $te['signatures_failed_verify'] }}</dd></div>
                <div class="col-span-2 md:col-span-4 text-xs text-gray-500"><span class="font-medium text-gray-700">Algorithm:</span> {{ $te['algorithm'] }}</div>
            </div>
        </div>

        {{-- 3. Consents breakdown --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Consents</h3>
                <p class="text-sm text-gray-500">By user choice and category, over {{ $report['period']['days'] }} days.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                <div class="p-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">By method</h4>
                    <dl class="space-y-2 text-sm">
                        @foreach ($report['consents']['by_method'] as $method => $count)
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-700">{{ str_replace('_', ' ', ucfirst($method)) }}</dt>
                                <dd class="font-mono text-gray-900">{{ number_format($count) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="p-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Acceptance by category</h4>
                    <dl class="space-y-3 text-sm">
                        @foreach ($report['consents']['by_category_acceptance_pct'] as $cat => $pct)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <dt class="text-gray-700">{{ ucfirst($cat) }}</dt>
                                    <dd class="font-mono text-gray-900">{{ $pct }}%</dd>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>

        {{-- 4. Google Consent Mode v2 --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Google Consent Mode v2 signals</h3>
                <p class="text-sm text-gray-500">Granted vs. observed counts. Required by Google for ad / analytics traffic from EEA visitors.</p>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($report['consents']['gcm_v2_signals'] as $signal => $stats)
                    @php $rate = $stats['total'] > 0 ? round(($stats['granted'] / $stats['total']) * 100, 1) : 0; @endphp
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ str_replace('_', ' ', $signal) }}</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900">{{ $rate }}%</div>
                        <div class="text-xs text-gray-500">{{ number_format($stats['granted']) }} granted / {{ number_format($stats['total']) }} observed</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 5. Cookie inventory --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Cookie inventory</h3>
                    <p class="text-sm text-gray-500">Detected by the scanner. Last scanned {{ $report['cookies']['last_scan_at'] ? \Carbon\Carbon::parse($report['cookies']['last_scan_at'])->diffForHumans() : '— never' }}.</p>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ number_format($report['cookies']['detected_count']) }}</span>
            </div>
            <div class="p-6">
                @if (empty($report['cookies']['by_category']))
                    <p class="text-sm text-gray-500">No cookies catalogued yet. Run a scan from your WordPress dashboard.</p>
                @else
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        @foreach ($report['cookies']['by_category'] as $cat => $count)
                            <div class="rounded-md border border-gray-200 p-3">
                                <dt class="text-xs text-gray-500 uppercase">{{ $cat ?: 'unclassified' }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ number_format($count) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        {{-- 6. DSAR --}}
        @php $dsar = $report['dsar']; @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Data Subject Access Requests</h3>
                <p class="text-sm text-gray-500">GDPR Articles 15–22 — access, erasure, portability. SLA: {{ $dsar['sla_target_days'] }} days.</p>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Total</dt><dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $dsar['total_requests'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Completed</dt><dd class="mt-1 text-2xl font-semibold text-emerald-700">{{ $dsar['completed'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Pending</dt><dd class="mt-1 text-2xl font-semibold text-amber-700">{{ $dsar['pending'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Rejected</dt><dd class="mt-1 text-2xl font-semibold text-gray-500">{{ $dsar['rejected'] }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">SLA breaches</dt><dd class="mt-1 text-2xl font-semibold {{ $dsar['sla_breaches'] > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ $dsar['sla_breaches'] }}</dd></div>
                <div class="col-span-2 md:col-span-5 pt-2 border-t border-gray-100">
                    <dt class="text-xs text-gray-500 uppercase tracking-wider">Average response time</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $dsar['avg_response_days'] !== null ? $dsar['avg_response_days'] . ' days' : 'No completed requests yet' }}</dd>
                </div>
            </div>
        </div>

        {{-- 7. Retention --}}
        @php $ret = $report['retention']; @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Retention</h3>
                <p class="text-sm text-gray-500">{{ $ret['privacy_policy_claim'] }}</p>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Retention window</dt><dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $ret['consent_expiry_days'] }} days</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Records in period</dt><dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($ret['consent_records_in_period']) }}</dd></div>
                <div><dt class="text-xs text-gray-500 uppercase tracking-wider">Past expiry, pending purge</dt><dd class="mt-1 text-2xl font-semibold {{ $ret['records_past_expiry_pending_purge'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">{{ number_format($ret['records_past_expiry_pending_purge']) }}</dd></div>
                <div class="col-span-2 md:col-span-3 text-xs text-gray-500"><span class="font-medium text-gray-700">Schedule:</span> {{ $ret['auto_purge_schedule'] }}</div>
            </div>
        </div>

        {{-- 8. Banner config snapshot --}}
        @php $bc = $report['banner_config']; @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Banner configuration snapshot</h3>
                <p class="text-sm text-gray-500">As of report generation. Auditors will check this against the policy text.</p>
            </div>
            <dl class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Layout</dt><dd class="text-gray-900">{{ $bc['banner_layout'] }} · {{ $bc['banner_position'] }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Reject-all visible</dt><dd class="text-gray-900">{{ $bc['show_reject_all'] ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Auto-block scripts</dt><dd class="text-gray-900">{{ $bc['auto_block_scripts'] ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Log consents</dt><dd class="text-gray-900">{{ $bc['log_consents'] ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Respect DNT header</dt><dd class="text-gray-900">{{ $bc['respect_dnt'] ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Google Consent Mode v2</dt><dd class="text-gray-900">{{ $bc['gcm_v2_enabled'] ? 'Enabled' : 'Disabled' }} · wait-for-update {{ $bc['gcm_wait_for_update'] ? 'on' : 'off' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">IAB TCF v2.2</dt><dd class="text-gray-900">{{ $bc['tcf_22_enabled'] ? 'Enabled' : 'Disabled' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Geo targeting</dt><dd class="text-gray-900">{{ ucfirst($bc['geo_targeting_mode']) }}{{ $bc['geo_targeting_mode'] === 'selected' && ! empty($bc['geo_countries']) ? ' (' . implode(', ', $bc['geo_countries']) . ')' : '' }}</dd></div>
                <div class="flex justify-between border-b border-gray-100 pb-2"><dt class="text-gray-600">Policy version</dt><dd class="text-gray-900">v{{ $bc['policy_version'] }}{{ $bc['policy_last_updated'] ? ' · updated ' . \Carbon\Carbon::parse($bc['policy_last_updated'])->diffForHumans() : '' }}</dd></div>
            </dl>
        </div>

        {{-- 9. Sub-processors --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Sub-processors</h3>
                <p class="text-sm text-gray-500">Third parties that process personal data on behalf of {{ config('app.name') }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3">Data</th>
                            <th class="px-6 py-3">Region</th>
                            <th class="px-6 py-3">Transfer mechanism</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($report['sub_processors'] as $sp)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $sp['name'] }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $sp['role'] }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $sp['data'] }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $sp['region'] }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $sp['sccs'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 10. Top countries (small, optional context) --}}
        @if (! empty($report['consents']['top_countries']))
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Top consenting countries</h3>
                <dl class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                    @foreach ($report['consents']['top_countries'] as $cc => $count)
                        <div class="rounded-md bg-gray-50 px-3 py-2 flex items-center justify-between">
                            <dt class="font-medium text-gray-900">{{ $cc ?: '—' }}</dt>
                            <dd class="font-mono text-gray-700">{{ number_format($count) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        <p class="text-xs text-gray-400 text-center pt-4">{{ $report['metadata']['generator'] }}</p>
    </div>
</x-app-layout>
