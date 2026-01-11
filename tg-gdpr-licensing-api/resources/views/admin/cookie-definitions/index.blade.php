@extends('layouts.admin')

@section('title', 'Cookie Definitions')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Global Cookie Definitions</h1>
                <p class="text-gray-600">Manage the global cookie database used across all sites</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.cookie-definitions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Cookie
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Total Cookies</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] ?? $cookies->count() }}</p>
            </div>
            <div class="bg-gray-100 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-700">Necessary</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $stats['necessary'] ?? $cookies->where('category', 'necessary')->count() }}</p>
            </div>
            <div class="bg-green-100 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-green-700">Functional</p>
                <p class="text-2xl font-semibold text-green-900">{{ $stats['functional'] ?? $cookies->where('category', 'functional')->count() }}</p>
            </div>
            <div class="bg-blue-100 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-blue-700">Analytics</p>
                <p class="text-2xl font-semibold text-blue-900">{{ $stats['analytics'] ?? $cookies->where('category', 'analytics')->count() }}</p>
            </div>
            <div class="bg-purple-100 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-purple-700">Marketing</p>
                <p class="text-2xl font-semibold text-purple-900">{{ $stats['marketing'] ?? $cookies->where('category', 'marketing')->count() }}</p>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4">
                <form action="{{ route('admin.cookie-definitions.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by cookie name or provider..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Categories</option>
                            <option value="necessary" {{ request('category') === 'necessary' ? 'selected' : '' }}>Necessary</option>
                            <option value="functional" {{ request('category') === 'functional' ? 'selected' : '' }}>Functional</option>
                            <option value="analytics" {{ request('category') === 'analytics' ? 'selected' : '' }}>Analytics</option>
                            <option value="marketing" {{ request('category') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                        </select>
                    </div>

                    <div>
                        <select name="source" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Sources</option>
                            <option value="open_database" {{ request('source') === 'open_database' ? 'selected' : '' }}>Open Database</option>
                            <option value="scanned" {{ request('source') === 'scanned' ? 'selected' : '' }}>Scanned</option>
                            <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="ai_categorized" {{ request('source') === 'ai_categorized' ? 'selected' : '' }}>AI Categorized</option>
                        </select>
                    </div>

                    <div>
                        <select name="verified" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>Verified</option>
                            <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>Unverified</option>
                        </select>
                    </div>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Cookie Definitions Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cookie Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cookies as $cookie)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $cookie->cookie_name }}</code>
                                @if($cookie->is_regex)
                                    <span class="ml-1 text-xs text-blue-600">(regex)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $catClass = match($cookie->category) {
                                        'necessary' => 'bg-gray-100 text-gray-800',
                                        'functional' => 'bg-green-100 text-green-800',
                                        'analytics' => 'bg-blue-100 text-blue-800',
                                        'marketing' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $catClass }}">
                                    {{ ucfirst($cookie->category) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $cookie->provider ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $cookie->description }}">
                                {{ Str::limit($cookie->description, 50) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $cookie->duration ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $sourceClass = match($cookie->source) {
                                        'open_database' => 'bg-green-100 text-green-800',
                                        'scanned' => 'bg-yellow-100 text-yellow-800',
                                        'manual' => 'bg-blue-100 text-blue-800',
                                        'ai_categorized' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $sourceClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $cookie->source)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($cookie->verified)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Unverified
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.cookie-definitions.edit', $cookie) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Edit
                                </a>
                                @if(!$cookie->verified)
                                    <form action="{{ route('admin.cookie-definitions.verify', $cookie) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900 mr-3">
                                            Verify
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.cookie-definitions.destroy', $cookie) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this cookie definition?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No cookie definitions</h3>
                                <p class="mt-1 text-sm text-gray-500">Run the seeder to populate common cookies or add them manually.</p>
                                <div class="mt-4">
                                    <a href="{{ route('admin.cookie-definitions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                        Add Cookie
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if(method_exists($cookies, 'hasPages') && $cookies->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $cookies->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Import Section -->
        <div class="mt-6 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Import Cookie Definitions</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.cookie-definitions.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="file" name="file" accept=".csv,.json" 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Import
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Supported formats: CSV, JSON. Max file size: 2MB.</p>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Global Cookie Database</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>This global database is shared across all sites. When a site scans for cookies, they are automatically matched against these definitions. Verified cookies are prioritized in search results and auto-categorization.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
