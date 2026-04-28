<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My Sites') }}</h2>
            <span class="text-sm text-gray-500">{{ $sites->total() }} site{{ $sites->total() === 1 ? '' : 's' }}</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                @if ($sites->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        <p class="text-lg font-medium text-gray-700 mb-2">No sites yet</p>
                        <p>Activate your license on a WordPress site to see it here.</p>
                        <a href="{{ route('customer.licenses.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            View licenses
                        </a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3">Site</th>
                                <th class="px-6 py-3">License</th>
                                <th class="px-6 py-3">Activations</th>
                                <th class="px-6 py-3">Consents (30d)</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($sites as $site)
                                @php
                                    $activeActivations = $site->license?->activations?->where('status', 'active')->count() ?? 0;
                                    $maxActivations    = $site->license?->max_activations ?? 1;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $site->site_name ?: $site->domain }}</div>
                                        <div class="text-xs text-gray-500"><a href="https://{{ $site->domain }}" target="_blank" rel="noopener" class="hover:text-indigo-600">{{ $site->domain }} ↗</a></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-mono text-xs text-gray-700">{{ $site->license?->license_key ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $site->license?->plan ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $activeActivations }} / {{ $maxActivations }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ number_format($site->consents_count_30d) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($site->consents_count_total) }} all-time</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($site->status === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        @elseif ($site->status === 'trial')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Trial</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($site->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                        <a href="{{ route('customer.sites.show', $site) }}" class="text-indigo-600 hover:text-indigo-800">Manage</a>
                                        <a href="{{ route('customer.sites.analytics', $site) }}" class="text-indigo-600 hover:text-indigo-800">Analytics</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4">{{ $sites->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
