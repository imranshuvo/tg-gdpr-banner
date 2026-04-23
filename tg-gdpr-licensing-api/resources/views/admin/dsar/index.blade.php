@extends('layouts.admin')

@section('title', 'DSAR Requests')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Subject Access Requests</h1>
                <p class="text-gray-600">Manage GDPR rights requests (Articles 15-22)</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-yellow-700">Pending Verification</p>
                <p class="text-2xl font-semibold text-yellow-900">{{ $stats['pending'] ?? 0 }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-blue-700">Verified</p>
                <p class="text-2xl font-semibold text-blue-900">{{ $stats['verified'] ?? 0 }}</p>
            </div>
            <div class="bg-indigo-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-indigo-700">Processing</p>
                <p class="text-2xl font-semibold text-indigo-900">{{ $stats['processing'] ?? 0 }}</p>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-green-700">Completed</p>
                <p class="text-2xl font-semibold text-green-900">{{ $stats['completed'] ?? 0 }}</p>
            </div>
            <div class="bg-red-50 rounded-lg shadow p-4">
                <p class="text-sm font-medium text-red-700">Overdue</p>
                <p class="text-2xl font-semibold text-red-900">{{ $stats['overdue'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4">
                <form action="{{ route('admin.dsar.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                        <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Types</option>
                            <option value="access" {{ request('type') === 'access' ? 'selected' : '' }}>Access (Art. 15)</option>
                            <option value="rectification" {{ request('type') === 'rectification' ? 'selected' : '' }}>Rectification (Art. 16)</option>
                            <option value="erasure" {{ request('type') === 'erasure' ? 'selected' : '' }}>Erasure (Art. 17)</option>
                            <option value="restriction" {{ request('type') === 'restriction' ? 'selected' : '' }}>Restriction (Art. 18)</option>
                            <option value="portability" {{ request('type') === 'portability' ? 'selected' : '' }}>Portability (Art. 20)</option>
                            <option value="objection" {{ request('type') === 'objection' ? 'selected' : '' }}>Objection (Art. 21)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Statuses</option>
                            <option value="pending_verification" {{ request('status') === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select name="site_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Sites</option>
                            @foreach($sites ?? [] as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>{{ $site->domain }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end space-x-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filter
                        </button>
                        <a href="{{ route('admin.dsar.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visitor Hash</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-50 {{ $request->isOverdue() ? 'bg-red-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $request->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $request->site->domain ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $typeInfo = match($request->request_type) {
                                        'access' => ['Access', 'Art. 15', 'bg-blue-100 text-blue-800'],
                                        'rectification' => ['Rectification', 'Art. 16', 'bg-yellow-100 text-yellow-800'],
                                        'erasure' => ['Erasure', 'Art. 17', 'bg-red-100 text-red-800'],
                                        'restriction' => ['Restriction', 'Art. 18', 'bg-cyan-100 text-cyan-800'],
                                        'portability' => ['Portability', 'Art. 20', 'bg-purple-100 text-purple-800'],
                                        'objection' => ['Objection', 'Art. 21', 'bg-orange-100 text-orange-800'],
                                        default => ['Unknown', '', 'bg-gray-100 text-gray-800'],
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $typeInfo[2] }}">
                                    {{ $typeInfo[0] }}
                                </span>
                                <span class="text-xs text-gray-500 ml-1">{{ $typeInfo[1] }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->requester_name ?: 'Unspecified' }}</div>
                                <div class="text-xs text-gray-500">{{ $request->requester_email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($request->visitor_hash)
                                    <code class="rounded bg-gray-100 px-2 py-1 text-xs">{{ \Illuminate\Support\Str::limit($request->visitor_hash, 16, '...') }}</code>
                                @else
                                    <span class="text-xs text-amber-700">Missing</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusClass = match($request->status) {
                                        'pending_verification' => 'bg-yellow-100 text-yellow-800',
                                        'verified' => 'bg-blue-100 text-blue-800',
                                        'processing' => 'bg-indigo-100 text-indigo-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $request->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $deadline = $request->due_date ?? $request->created_at->copy()->addDays(30);
                                    $daysLeft = now()->diffInDays($deadline, false);
                                    $isOverdue = $daysLeft < 0;
                                @endphp
                                <div class="text-sm {{ $isOverdue ? 'text-red-600 font-medium' : ($daysLeft <= 7 ? 'text-yellow-600' : 'text-gray-500') }}">
                                    {{ $deadline->format('M d, Y') }}
                                    @if($request->status !== 'completed' && $request->status !== 'rejected')
                                        <div class="text-xs">
                                            @if($isOverdue)
                                                {{ abs($daysLeft) }} days overdue
                                            @else
                                                {{ $daysLeft }} days left
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.dsar.show', $request) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    View
                                </a>
                                @if($request->status === 'verified')
                                    <form action="{{ route('admin.dsar.start', $request) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            Start
                                        </button>
                                    </form>
                                @elseif($request->status === 'completed' && $request->data_export_path)
                                    <a href="{{ route('admin.dsar.download', $request) }}" class="text-green-600 hover:text-green-900">
                                        Download
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No DSAR requests</h3>
                                <p class="mt-1 text-sm text-gray-500">Data subject access requests will appear here when submitted.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if(method_exists($requests, 'hasPages') && $requests->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $requests->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- GDPR Compliance Info -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">GDPR Timeline Requirements</h3>
                <div class="text-sm text-blue-700 space-y-1">
                    <p>• <strong>30 days:</strong> Maximum response time for most requests</p>
                    <p>• <strong>60 days extension:</strong> Allowed for complex requests (must notify)</p>
                    <p>• <strong>Identity verification:</strong> Required before processing</p>
                    <p>• <strong>Free of charge:</strong> First request; may charge for repetitive requests</p>
                </div>
            </div>
            
            <div class="bg-purple-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-purple-800 mb-2">Request Types</h3>
                <div class="text-sm text-purple-700 space-y-1">
                    <p>• <strong>Access (Art. 15):</strong> Provide copy of personal data</p>
                    <p>• <strong>Rectification (Art. 16):</strong> Correct inaccurate data</p>
                    <p>• <strong>Erasure (Art. 17):</strong> Delete personal data ("Right to be forgotten")</p>
                    <p>• <strong>Portability (Art. 20):</strong> Export data in machine-readable format</p>
                    <p>• <strong>Objection (Art. 21):</strong> Object to processing</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
