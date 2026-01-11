@extends('layouts.admin')

@section('title', 'Cookies - ' . $site->domain)

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
                            <span class="ml-2 text-gray-500">Cookies</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Cookie Management</h1>
                <p class="text-gray-600">Manage detected and custom cookies for {{ $site->domain }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Add Cookie Button -->
                <button type="button" onclick="showAddCookieModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Cookie
                </button>
                
                <!-- Scan Button -->
                <button type="button" onclick="triggerScan()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Scan Site
                </button>
            </div>
        </div>

        <!-- Category Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @foreach(['necessary' => 'Necessary', 'functional' => 'Functional', 'analytics' => 'Analytics', 'marketing' => 'Marketing'] as $key => $label)
                @php 
                    $count = $cookies->where('category', $key)->count();
                    $bgClass = match($key) {
                        'necessary' => 'bg-gray-100 text-gray-800',
                        'functional' => 'bg-green-100 text-green-800',
                        'analytics' => 'bg-blue-100 text-blue-800',
                        'marketing' => 'bg-purple-100 text-purple-800',
                    };
                @endphp
                <div class="rounded-lg shadow p-4 {{ $bgClass }}">
                    <p class="text-sm font-medium">{{ $label }}</p>
                    <p class="text-2xl font-semibold">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        <!-- Cookies by Category -->
        @foreach(['necessary' => 'Necessary Cookies', 'functional' => 'Functional Cookies', 'analytics' => 'Analytics Cookies', 'marketing' => 'Marketing Cookies'] as $category => $title)
            @php $categoryCookies = $cookies->where('category', $category); @endphp
            @if($categoryCookies->count() > 0)
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $title }}
                            <span class="ml-2 text-sm text-gray-500">({{ $categoryCookies->count() }})</span>
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cookie Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($categoryCookies as $cookie)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $cookie->cookie_name }}</code>
                                            @if($cookie->is_regex)
                                                <span class="ml-1 text-xs text-blue-600">(regex)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $cookie->provider ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $cookie->description }}">
                                            {{ Str::limit($cookie->description, 60) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $cookie->duration ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $sourceClass = match($cookie->source) {
                                                    'scan' => 'bg-yellow-100 text-yellow-800',
                                                    'manual' => 'bg-blue-100 text-blue-800',
                                                    'global_db' => 'bg-green-100 text-green-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $sourceClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $cookie->source)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($cookie->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button type="button" onclick="editCookie({{ $cookie->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Edit
                                            </button>
                                            <button type="button" onclick="deleteCookie({{ $cookie->id }})" class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Global Cookie Database Section -->
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Import from Global Database</h3>
                <p class="text-sm text-gray-500 mt-1">Search and import cookie definitions from our database of 50+ common cookies</p>
            </div>
            <div class="p-6">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" 
                               id="cookieSearch" 
                               placeholder="Search cookies (e.g., _ga, facebook, analytics...)" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="button" 
                            onclick="searchGlobalCookies()" 
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Search
                    </button>
                </div>
                <div id="searchResults" class="mt-4 hidden">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Cookie Categories</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Necessary:</strong> Essential for the website to function. Always enabled.</li>
                            <li><strong>Functional:</strong> Enable enhanced features like chat widgets, maps, etc.</li>
                            <li><strong>Analytics:</strong> Help understand visitor behavior (Google Analytics, Hotjar, etc.)</li>
                            <li><strong>Marketing:</strong> Used for advertising and retargeting (Facebook Pixel, Google Ads, etc.)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Cookie Modal -->
<div id="cookieModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg max-w-lg w-full shadow-xl">
            <form id="cookieForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="cookie_id" id="cookieId">
                
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add Cookie</h3>
                    <button type="button" onclick="closeCookieModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cookie Name</label>
                        <input type="text" name="cookie_name" id="inputCookieName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category" id="inputCategory" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="necessary">Necessary</option>
                            <option value="functional">Functional</option>
                            <option value="analytics">Analytics</option>
                            <option value="marketing">Marketing</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Provider</label>
                        <input type="text" name="provider" id="inputProvider"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="inputDescription" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duration</label>
                        <input type="text" name="duration" id="inputDuration" placeholder="e.g., 1 year, Session, 30 days"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_regex" id="inputIsRegex" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="inputIsRegex" class="ml-2 text-sm text-gray-700">Cookie name is a regex pattern</label>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeCookieModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Save Cookie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showAddCookieModal() {
    document.getElementById('modalTitle').textContent = 'Add Cookie';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('cookieForm').reset();
    document.getElementById('cookieModal').classList.remove('hidden');
}

function editCookie(id) {
    document.getElementById('modalTitle').textContent = 'Edit Cookie';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('cookieId').value = id;
    // In real implementation, fetch cookie data and populate form
    document.getElementById('cookieModal').classList.remove('hidden');
}

function closeCookieModal() {
    document.getElementById('cookieModal').classList.add('hidden');
}

function deleteCookie(id) {
    if (confirm('Are you sure you want to delete this cookie?')) {
        // Submit delete request
    }
}

function triggerScan() {
    alert('Cookie scanning would be triggered here. This scans the live site for cookies.');
}

function searchGlobalCookies() {
    const query = document.getElementById('cookieSearch').value;
    if (query.length < 2) {
        alert('Please enter at least 2 characters');
        return;
    }
    // In real implementation, search via AJAX
    document.getElementById('searchResults').classList.remove('hidden');
    document.getElementById('searchResults').innerHTML = '<p class="text-gray-600">Searching for "' + query + '"...</p>';
}
</script>
@endpush
@endsection
