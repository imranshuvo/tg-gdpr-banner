<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('API Keys') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('new_api_key'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-lg font-medium text-blue-800">Your New API Key</h3>
                            <p class="mt-2 text-sm text-blue-700">
                                Please copy this API key now. For security reasons, it won't be shown again.
                            </p>
                            <div class="mt-4 flex items-center space-x-3">
                                <code id="api-key-value" class="flex-1 bg-white border border-blue-200 rounded px-4 py-3 text-sm font-mono text-gray-900">
                                    {{ session('new_api_key') }}
                                </code>
                                <button onclick="copyApiKey()" class="inline-flex items-center px-4 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- API Key Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">API Key Management</h3>
                    
                    @if($apiKey)
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current API Key</label>
                                <div class="flex items-center space-x-3">
                                    <code class="flex-1 bg-gray-50 border border-gray-300 rounded px-4 py-3 text-sm font-mono text-gray-900">
                                        {{ $apiKeyMasked }}
                                    </code>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    For security, only the first and last characters are shown.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($apiKeyCreatedAt)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Created</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($apiKeyCreatedAt)->format('F j, Y \a\t g:i A') }}
                                        </p>
                                    </div>
                                @endif

                                @if($lastUsedAt)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Last Used</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($lastUsedAt)->diffForHumans() }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-4 flex space-x-3">
                                <form action="{{ route('customer.api-keys.generate') }}" method="POST" onsubmit="return confirm('This will invalidate your current API key. Are you sure?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Regenerate API Key
                                    </button>
                                </form>

                                <form action="{{ route('customer.api-keys.revoke') }}" method="POST" onsubmit="return confirm('This will permanently delete your API key. Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Revoke API Key
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No API Key</h3>
                            <p class="mt-2 text-sm text-gray-600">You haven't generated an API key yet.</p>
                            <div class="mt-6">
                                <form action="{{ route('customer.api-keys.generate') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Generate API Key
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- API Documentation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">API Usage</h3>
                    <div class="prose max-w-none text-sm text-gray-600">
                        <p class="mb-4">
                            Use your API key to authenticate requests to our licensing API. Include it in the <code class="bg-gray-100 px-2 py-1 rounded text-xs">Authorization</code> header:
                        </p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-green-400 text-xs"><code>curl -X GET https://{{ config('app.url') }}/api/v1/licenses \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json"</code></pre>
                        </div>

                        <div class="mt-6">
                            <h4 class="font-semibold text-gray-900 mb-2">Available Endpoints:</h4>
                            <ul class="space-y-2">
                                <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">GET /api/v1/licenses</code> - List all your licenses</li>
                                <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">GET /api/v1/licenses/{key}</code> - Get license details</li>
                                <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">POST /api/v1/licenses/{key}/activate</code> - Activate license</li>
                                <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">POST /api/v1/licenses/{key}/deactivate</code> - Deactivate license</li>
                                <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">POST /api/v1/licenses/{key}/check</code> - Verify license</li>
                            </ul>
                        </div>

                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">Security Notice</h4>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        Keep your API key secure! Never share it publicly or commit it to version control. 
                                        If your key is compromised, regenerate it immediately.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function copyApiKey() {
            const apiKeyElement = document.getElementById('api-key-value');
            const apiKey = apiKeyElement.textContent.trim();
            
            navigator.clipboard.writeText(apiKey).then(() => {
                // Show success feedback
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Copied!';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy API key. Please copy it manually.');
            });
        }
    </script>
</x-app-layout>
