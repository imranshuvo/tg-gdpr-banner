<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $site->site_name ?: $site->domain }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    <a href="https://{{ $site->domain }}" target="_blank" rel="noopener" class="hover:text-indigo-600">{{ $site->domain }} ↗</a>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('customer.sites.analytics', $site) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">View analytics</a>
                <a href="{{ route('customer.sites.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← All sites</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif

        {{-- License + status --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6 grid sm:grid-cols-3 gap-6">
            <div>
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">License</dt>
                <dd class="mt-1 font-mono text-sm text-gray-900">{{ $site->license?->license_key ?? '—' }}</dd>
                <dd class="text-xs text-gray-500 mt-0.5">{{ $site->license?->plan ?? '—' }} · {{ $site->license?->status ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</dt>
                <dd class="mt-1">
                    @if ($site->status === 'active')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($site->status) }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Site token</dt>
                <dd class="mt-1 font-mono text-xs text-gray-700 break-all">{{ Str::limit($site->site_token, 32) }}</dd>
                <dd class="text-xs text-gray-500 mt-0.5">Used by the WordPress plugin to authenticate</dd>
            </div>
        </div>

        {{-- Activations on this site's license --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Activated domains</h3>
                <p class="text-sm text-gray-500">Each row is a WordPress install using this license. Deactivating frees a slot for a new install.</p>
            </div>
            @php $activations = $site->license?->activations ?? collect(); @endphp
            @if ($activations->isEmpty())
                <div class="p-6 text-sm text-gray-500">No activations yet.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Domain</th>
                            <th class="px-6 py-3">Last check</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($activations as $a)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $a->domain }}</div>
                                    <div class="text-xs text-gray-500">{{ $a->site_url }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $a->last_check_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($a->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if ($a->status === 'active')
                                        <form method="POST" action="{{ route('customer.sites.deactivate-activation', ['site' => $site, 'activation' => $a]) }}" onsubmit="return confirm('Deactivate {{ $a->domain }}? This frees a slot for a new site.')">
                                            @csrf @method('DELETE')
                                            <button class="text-sm text-red-600 hover:text-red-800">Deactivate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Recent consent records --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent consent records</h3>
                <p class="text-sm text-gray-500">Last 20 consents recorded on this site. <a href="{{ route('customer.sites.analytics', $site) }}" class="text-indigo-600 hover:underline">See analytics →</a></p>
            </div>
            @if ($recentConsents->isEmpty())
                <div class="p-6 text-sm text-gray-500">No consent records yet.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">When</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Categories</th>
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
                                <td class="px-6 py-4 text-xs text-gray-700">
                                    @foreach (($c->consent_categories ?? []) as $cat => $given)
                                        <span class="{{ $given ? 'text-green-700' : 'text-gray-400 line-through' }}">{{ $cat }}</span>{{ ! $loop->last ? ' · ' : '' }}
                                    @endforeach
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
