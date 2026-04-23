<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('License Details') }}
            </h2>
            <a href="{{ route('customer.licenses.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                ← Back to Licenses
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- License Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">License Information</h3>
                            <code class="text-2xl font-mono bg-gray-100 px-3 py-2 rounded">{{ $license->license_key }}</code>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('customer.licenses.download', $license) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download License
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Plan</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ ucfirst($license->plan) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="mt-1">
                                @if($license->status === 'active' && !$license->isExpired())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($license->isExpired())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">
                                        {{ ucfirst($license->status) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Max Activations</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $license->max_activations }} sites</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Expires</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $license->expires_at->format('M d, Y') }}</p>
                            @if($license->expires_at->isPast())
                                <p class="text-xs text-red-600 mt-1">Expired {{ $license->expires_at->diffForHumans() }}</p>
                            @elseif($license->expires_at->diffInDays() < 30)
                                <p class="text-xs text-yellow-600 mt-1">Expires {{ $license->expires_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Created</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $license->created_at->format('F j, Y \a\t g:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Last Updated</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $license->updated_at->format('F j, Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Sites -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Active Sites</h3>
                        <div class="text-sm">
                            <span class="font-medium text-gray-700">
                                {{ $license->activations->where('status', 'active')->count() }} / {{ $license->max_activations }}
                            </span>
                            <span class="text-gray-500">sites active</span>
                        </div>
                    </div>

                    @php
                        $activeSites = $license->activations->where('status', 'active');
                    @endphp

                    @if($activeSites->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site URL</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activated</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Heartbeat</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($activeSites as $activation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ $activation->site_url }}</div>
                                                        <div class="text-xs text-gray-500">{{ $activation->site_ip ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $activation->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($activation->last_check)
                                                    {{ $activation->last_check->diffForHumans() }}
                                                @else
                                                    Never
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $isStale = $activation->last_check && $activation->last_check->diffInDays() > 7;
                                                @endphp
                                                @if($isStale)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Inactive
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">No active sites</p>
                            <p class="mt-1 text-xs text-gray-500">This license hasn't been activated on any sites yet.</p>
                        </div>
                    @endif

                    @if(!$license->canActivate() && !$license->isExpired())
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Activation Limit Reached</h3>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        You've reached the maximum number of activations for this license. 
                                        To activate on a new site, you'll need to deactivate from an existing site first.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Installation Instructions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Setup Instructions</h3>
                    <div class="prose max-w-none text-sm text-gray-600">
                        <ol class="space-y-3">
                            <li>
                                <strong>Open Cookiely:</strong> Access the Cookiely integration for the site you want to activate.
                            </li>
                            <li>
                                <strong>Open the License Screen:</strong> Navigate to the license settings in Cookiely.
                            </li>
                            <li>
                                <strong>Activate:</strong> Paste your license key: <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $license->license_key }}</code>
                            </li>
                            <li>
                                <strong>Click Activate:</strong> Cookiely will verify your license and unlock all premium features.
                            </li>
                        </ol>
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>Need Help?</strong> Contact our support team at <a href="mailto:support@example.com" class="underline">support@example.com</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
