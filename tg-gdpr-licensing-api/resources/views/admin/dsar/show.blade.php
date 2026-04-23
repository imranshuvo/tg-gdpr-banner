@extends('layouts.admin')

@section('title', 'DSAR Request #' . $dsarRequest->id)

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('admin.dsar.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to DSAR Requests</a>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">DSAR Request #{{ $dsarRequest->id }}</h1>
                <p class="text-gray-600">{{ \App\Models\DsarRequest::getRequestTypeLabel($dsarRequest->request_type) }}</p>
            </div>

            <div>
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold
                    {{ match($dsarRequest->status) {
                        'pending_verification' => 'bg-yellow-100 text-yellow-800',
                        'verified' => 'bg-blue-100 text-blue-800',
                        'processing' => 'bg-indigo-100 text-indigo-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">
                    {{ ucfirst(str_replace('_', ' ', $dsarRequest->status)) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Request Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Requester</p>
                        <p class="font-medium text-gray-900">{{ $dsarRequest->requester_name ?: 'Unspecified' }}</p>
                        <p class="text-gray-700">{{ $dsarRequest->requester_email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Site</p>
                        <p class="font-medium text-gray-900">{{ $dsarRequest->site?->domain ?? 'Customer-wide request' }}</p>
                        <p class="text-gray-700">{{ $dsarRequest->customer?->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Submitted</p>
                        <p class="font-medium text-gray-900">{{ $dsarRequest->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Due Date</p>
                        <p class="font-medium text-gray-900">{{ optional($dsarRequest->due_date)->format('Y-m-d H:i:s') ?: 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Visitor Hash</p>
                        @if($dsarRequest->visitor_hash)
                            <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 break-all">{{ $dsarRequest->visitor_hash }}</code>
                        @else
                            <p class="text-amber-700">No visitor hash was supplied with this request.</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-gray-500">Verification</p>
                        <p class="font-medium text-gray-900">{{ optional($dsarRequest->verified_at)->format('Y-m-d H:i:s') ?: 'Not yet verified' }}</p>
                    </div>
                    @if($dsarRequest->requester_phone)
                        <div>
                            <p class="text-gray-500">Phone</p>
                            <p class="font-medium text-gray-900">{{ $dsarRequest->requester_phone }}</p>
                        </div>
                    @endif
                    @if($dsarRequest->processor)
                        <div>
                            <p class="text-gray-500">Processed By</p>
                            <p class="font-medium text-gray-900">{{ $dsarRequest->processor->name }}</p>
                        </div>
                    @endif
                    @if($dsarRequest->additional_info)
                        <div class="md:col-span-2">
                            <p class="text-gray-500">Additional Information</p>
                            <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $dsarRequest->additional_info }}</p>
                        </div>
                    @endif
                    @if($dsarRequest->admin_notes)
                        <div class="md:col-span-2">
                            <p class="text-gray-500">Admin Notes</p>
                            <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $dsarRequest->admin_notes }}</p>
                        </div>
                    @endif
                    @if($dsarRequest->rejection_reason)
                        <div class="md:col-span-2">
                            <p class="text-gray-500">Rejection Reason</p>
                            <p class="mt-1 text-red-700 whitespace-pre-line">{{ $dsarRequest->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                @if($dsarRequest->status === 'verified')
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-3">Start Processing</h2>
                        <p class="text-sm text-gray-600 mb-4">Verification is complete. Start processing when you are ready to work this request.</p>
                        <form action="{{ route('admin.dsar.start', $dsarRequest) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                                Start Processing
                            </button>
                        </form>
                    </div>
                @endif

                @if($dsarRequest->status === 'processing')
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-3">Complete or Reject</h2>
                        @if($dsarRequest->requiresScopedConsentLookup() && !$dsarRequest->hasVisitorHash())
                            <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                                This request cannot be auto-processed because it is missing a visitor hash. Add notes and reject it, or resolve it manually outside the automated consent log.
                            </div>
                        @endif
                        <form action="{{ route('admin.dsar.process', $dsarRequest) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                                <textarea id="admin_notes" name="admin_notes" rows="5" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('admin_notes', $dsarRequest->admin_notes) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Use this for manual rectification details, requester communication notes, or internal audit history.</p>
                            </div>
                            <div>
                                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                                <input id="rejection_reason" name="rejection_reason" type="text" value="{{ old('rejection_reason') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="submit" name="action" value="complete" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-green-700">
                                    Complete Request
                                </button>
                                <button type="submit" name="action" value="reject" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-red-700">
                                    Reject Request
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if($dsarRequest->status === 'completed' && $dsarRequest->data_export_path)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-3">Export</h2>
                        <p class="text-sm text-gray-600 mb-4">A data export was generated for this request.</p>
                        <a href="{{ route('admin.dsar.download', $dsarRequest) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                            Download Export
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Scoped Consent Records</h2>
                <span class="text-sm text-gray-500">{{ $consentRecords->count() }} records</span>
            </div>

            @if($consentRecords->isEmpty())
                <div class="p-6 text-sm text-gray-500">
                    No consent records matched this request.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consent ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @foreach($consentRecords as $record)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $record->consent_id }}</code></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $record->site?->domain ?? 'Unknown' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ ucfirst(str_replace('_', ' ', $record->consent_method ?? 'unknown')) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $record->country_code ?: 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $record->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($record->withdrawn_at)
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Withdrawn</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection