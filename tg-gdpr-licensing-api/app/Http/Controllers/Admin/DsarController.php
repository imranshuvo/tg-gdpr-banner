<?php

namespace App\Http\Controllers\Admin;

use App\Mail\DsarCompletedMail;
use App\Mail\DsarRejectedMail;
use App\Http\Controllers\Controller;
use App\Models\DsarRequest;
use App\Models\ConsentRecord;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DsarController extends Controller
{
    /**
     * Display all DSAR requests
     */
    public function index(Request $request)
    {
        $query = DsarRequest::with(['site', 'customer', 'processor']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('request_type', $request->type);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        
        // Show overdue first, then by due date
        $query->orderByRaw('CASE WHEN due_date < NOW() AND status NOT IN ("completed", "rejected", "cancelled") THEN 0 ELSE 1 END')
              ->orderBy('due_date');
        
        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => DsarRequest::count(),
            'pending' => DsarRequest::where('status', 'pending_verification')->count(),
            'verified' => DsarRequest::where('status', 'verified')->count(),
            'processing' => DsarRequest::where('status', 'processing')->count(),
            'completed' => DsarRequest::where('status', 'completed')->count(),
            'overdue' => DsarRequest::where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])
                ->count(),
        ];

        $sites = Site::query()->orderBy('domain')->get(['id', 'domain']);

        return view('admin.dsar.index', compact('requests', 'stats', 'sites'));
    }

    /**
     * Show DSAR request details
     */
    public function show(DsarRequest $dsarRequest)
    {
        $dsarRequest->load(['site', 'customer', 'processor']);

        $consentRecords = $this->scopedConsentRecordsQuery($dsarRequest)
            ->with('site')
            ->latest()
            ->limit(50)
            ->get();
        
        return view('admin.dsar.show', compact('dsarRequest', 'consentRecords'));
    }

    /**
     * Start processing a DSAR request
     */
    public function startProcessing(DsarRequest $dsarRequest)
    {
        if ($dsarRequest->status !== 'verified') {
            return back()->with('error', 'Request must be verified before processing.');
        }
        
        $dsarRequest->startProcessing(auth()->id());
        
        return back()->with('success', 'Processing started.');
    }

    /**
     * Process and complete a DSAR request
     */
    public function process(Request $request, DsarRequest $dsarRequest)
    {
        $request->validate([
            'action' => 'required|in:complete,reject',
            'admin_notes' => 'nullable|string|max:2000',
            'rejection_reason' => 'required_if:action,reject|string|max:500',
        ]);

        if ($request->action === 'complete' && $dsarRequest->request_type === 'rectification' && blank($request->admin_notes)) {
            return back()->with('error', 'Add admin notes describing the rectification action before completing this request.');
        }

        if ($request->action === 'complete' && $dsarRequest->requiresScopedConsentLookup() && !$dsarRequest->hasVisitorHash()) {
            return back()->with('error', 'This request cannot be auto-processed because no visitor hash was supplied with the request.');
        }

        if ($request->action === 'complete' && $dsarRequest->requiresScopedConsentLookup()) {
            $matchingRecords = $this->scopedConsentRecordsQuery($dsarRequest)->count();

            if ($matchingRecords === 0 && blank($request->admin_notes)) {
                return back()->with('error', 'No consent records matched this visitor hash. Add admin notes before completing the request so the zero-data outcome is explicitly reviewed.');
            }
        }
        
        $dsarRequest->update(['admin_notes' => $request->admin_notes]);
        
        if ($request->action === 'reject') {
            $dsarRequest->reject($request->rejection_reason);
            Mail::to($dsarRequest->requester_email)->send(new DsarRejectedMail($dsarRequest));

            return redirect()
                ->route('admin.dsar.index')
                ->with('success', 'Request rejected.');
        }
        
        // Process based on request type
        $exportPath = null;
        
        switch ($dsarRequest->request_type) {
            case 'access':
            case 'portability':
                $exportPath = $this->generateDataExport($dsarRequest);
                break;
                
            case 'erasure':
                $this->processErasure($dsarRequest);
                break;
                
            case 'restriction':
            case 'objection':
                $this->processRestriction($dsarRequest);
                break;
        }
        
        $dsarRequest->complete($exportPath);

        Mail::to($dsarRequest->requester_email)->send(
            new DsarCompletedMail($dsarRequest, $this->getDownloadUrl($dsarRequest))
        );
        
        return redirect()
            ->route('admin.dsar.index')
            ->with('success', 'Request completed successfully.');
    }

    /**
     * Generate data export for access/portability requests
     */
    private function generateDataExport(DsarRequest $dsarRequest): string
    {
        $data = [
            'request_info' => [
                'type' => $dsarRequest->request_type,
                'request_type_label' => DsarRequest::getRequestTypeLabel($dsarRequest->request_type),
                'requester_email' => $dsarRequest->requester_email,
                'requester_name' => $dsarRequest->requester_name,
                'visitor_hash' => $dsarRequest->visitor_hash,
                'requested_at' => $dsarRequest->created_at->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
            ],
            'consent_records' => [],
        ];

        $records = $this->scopedConsentRecordsQuery($dsarRequest)
            ->with('site')
            ->latest()
            ->limit(1000)
            ->get();
        
        foreach ($records as $record) {
            $data['consent_records'][] = [
                'consent_id' => $record->consent_id,
                'site_domain' => $record->site->domain ?? 'Unknown',
                'consent_given' => $record->consent_categories,
                'consent_method' => $record->consent_method,
                'policy_version' => $record->policy_version,
                'country_code' => $record->country_code,
                'device_type' => $record->device_type,
                'created_at' => $record->created_at->toIso8601String(),
                'withdrawn_at' => $record->withdrawn_at?->toIso8601String(),
            ];
        }
        
        // Save to storage
        $filename = 'dsar-exports/' . $dsarRequest->id . '-' . now()->format('Ymd-His') . '.json';
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filename;
    }

    /**
     * Process erasure request
     */
    private function processErasure(DsarRequest $dsarRequest): void
    {
        $this->scopedConsentRecordsQuery($dsarRequest)->delete();
    }

    /**
     * Process restriction request
     */
    private function processRestriction(DsarRequest $dsarRequest): void
    {
        $reason = $dsarRequest->request_type === 'objection'
            ? 'DSAR objection request'
            : 'DSAR restriction request';

        $this->scopedConsentRecordsQuery($dsarRequest)
            ->whereNull('withdrawn_at')
            ->update([
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
            ]);
    }

    /**
     * Download export file
     */
    public function download(DsarRequest $dsarRequest)
    {
        if (!$dsarRequest->data_export_path || !Storage::exists($dsarRequest->data_export_path)) {
            return back()->with('error', 'Export file not found.');
        }
        
        $dsarRequest->increment('download_count');
        
        return Storage::download($dsarRequest->data_export_path, sprintf('dsar-request-%d.json', $dsarRequest->id));
    }

    /**
     * Get the consent records scoped to the request subject.
     */
    private function scopedConsentRecordsQuery(DsarRequest $dsarRequest): Builder
    {
        $query = ConsentRecord::query();

        if ($dsarRequest->site_id) {
            $query->where('site_id', $dsarRequest->site_id);
        } else {
            $query->whereHas('site', function (Builder $siteQuery) use ($dsarRequest) {
                $siteQuery->where('customer_id', $dsarRequest->customer_id);
            });
        }

        if (!$dsarRequest->hasVisitorHash()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('visitor_hash', $dsarRequest->visitor_hash);
    }

    /**
     * Get the requester download URL for export-based requests.
     */
    private function getDownloadUrl(DsarRequest $dsarRequest): ?string
    {
        if (empty($dsarRequest->data_export_path) || empty($dsarRequest->verification_token)) {
            return null;
        }

        return url('/api/v1/dsar/download/' . $dsarRequest->verification_token);
    }
}
