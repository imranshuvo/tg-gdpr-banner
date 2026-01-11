@extends('layouts.admin')

@section('title', 'Consent Records - ' . $site->domain)

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
                            <span class="ml-2 text-gray-500">Consent Records</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Consent Records</h1>
                <p class="text-gray-600">GDPR compliant consent proof for {{ $site->domain }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Export Button -->
                <button type="button" onclick="exportConsents()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4">
                <form action="{{ route('admin.sites.consents', $site) }}" method="GET" class="flex flex-wrap gap-4">
                    <!-- Interaction Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Interaction Type</label>
                        <select name="interaction" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All</option>
                            <option value="accept_all" {{ request('interaction') === 'accept_all' ? 'selected' : '' }}>Accept All</option>
                            <option value="reject_all" {{ request('interaction') === 'reject_all' ? 'selected' : '' }}>Reject All</option>
                            <option value="custom" {{ request('interaction') === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <!-- Country -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select name="country" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Countries</option>
                            @foreach($countries ?? [] as $code => $name)
                                <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filter
                        </button>
                        <a href="{{ route('admin.sites.consents', $site) }}" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Total Records</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($consents->total()) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-green-700">Accept All</p>
                <p class="text-2xl font-semibold text-green-900">{{ number_format($stats['accept_all'] ?? 0) }}</p>
            </div>
            <div class="bg-red-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-red-700">Reject All</p>
                <p class="text-2xl font-semibold text-red-900">{{ number_format($stats['reject_all'] ?? 0) }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-blue-700">Custom Choices</p>
                <p class="text-2xl font-semibold text-blue-900">{{ number_format($stats['custom'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Consent Records Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visitor Hash</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interaction</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($consents as $consent)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $consent->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $consent->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $consent->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ Str::limit($consent->visitor_hash, 12) }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeClass = match($consent->interaction_type) {
                                        'accept_all' => 'bg-green-100 text-green-800',
                                        'reject_all' => 'bg-red-100 text-red-800',
                                        'custom' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $consent->interaction_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $consentData = $consent->consent_data;
                                    if (is_string($consentData)) {
                                        $consentData = json_decode($consentData, true);
                                    }
                                @endphp
                                <div class="flex space-x-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-600 text-white" title="Necessary">N</span>
                                    @if($consentData['functional'] ?? false)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-600 text-white" title="Functional">F</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-300 text-gray-600" title="Functional (Denied)">F</span>
                                    @endif
                                    @if($consentData['analytics'] ?? false)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-600 text-white" title="Analytics">A</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-300 text-gray-600" title="Analytics (Denied)">A</span>
                                    @endif
                                    @if($consentData['marketing'] ?? false)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-600 text-white" title="Marketing">M</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-300 text-gray-600" title="Marketing (Denied)">M</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="capitalize">{{ $consent->device_type ?? 'Unknown' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $consent->country_code ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                v{{ $consent->policy_version ?? '1.0' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button type="button" 
                                        onclick="showConsentDetails({{ $consent->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No consent records</h3>
                                <p class="mt-1 text-sm text-gray-500">Consent records will appear here once visitors interact with the cookie banner.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($consents->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $consents->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- GDPR Compliance Notice -->
        <div class="mt-6 bg-blue-50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">GDPR Compliance Notice</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>These consent records serve as proof of consent under GDPR Article 7. IP addresses are anonymized, and visitor hashes are one-way encrypted to protect user privacy while maintaining consent traceability.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consent Details Modal -->
<div id="consentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg max-w-lg w-full shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Consent Record Details</h3>
                <button type="button" onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="consentModalBody" class="p-6">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showConsentDetails(id) {
    // In a real implementation, this would fetch details via AJAX
    document.getElementById('consentModal').classList.remove('hidden');
    document.getElementById('consentModalBody').innerHTML = `
        <p class="text-gray-600">Loading consent details for record #${id}...</p>
    `;
}

function closeModal() {
    document.getElementById('consentModal').classList.add('hidden');
}

function exportConsents() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = window.location.pathname + '?' + params.toString();
}
</script>
@endpush
@endsection
