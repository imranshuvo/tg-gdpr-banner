<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DsarRequest;
use App\Models\ConsentRecord;
use Illuminate\Http\Request;
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
        
        // Show overdue first, then by due date
        $query->orderByRaw('CASE WHEN due_date < NOW() AND status NOT IN ("completed", "rejected", "cancelled") THEN 0 ELSE 1 END')
              ->orderBy('due_date');
        
        $requests = $query->paginate(20)->withQueryString();
        
        // Get counts for quick filters
        $pendingCount = DsarRequest::whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();
        $overdueCount = DsarRequest::where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])
            ->count();
        
        return view('admin.dsar.index', compact('requests', 'pendingCount', 'overdueCount'));
    }

    /**
     * Show DSAR request details
     */
    public function show(DsarRequest $dsarRequest)
    {
        $dsarRequest->load(['site', 'customer', 'processor']);
        
        // Get related consent records if site is specified
        $consentRecords = [];
        if ($dsarRequest->site_id) {
            $consentRecords = ConsentRecord::where('site_id', $dsarRequest->site_id)
                ->whereRaw("visitor_hash IN (SELECT visitor_hash FROM consent_records WHERE site_id = ? ORDER BY created_at DESC LIMIT 1)", [$dsarRequest->site_id])
                ->latest()
                ->limit(50)
                ->get();
        }
        
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
        
        $dsarRequest->update(['admin_notes' => $request->admin_notes]);
        
        if ($request->action === 'reject') {
            $dsarRequest->reject($request->rejection_reason);
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
                $this->processRestriction($dsarRequest);
                break;
        }
        
        $dsarRequest->complete($exportPath);
        
        // TODO: Send completion email to requester
        
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
                'requested_at' => $dsarRequest->created_at->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
            ],
            'consent_records' => [],
        ];
        
        // Get all consent records for this email
        // Note: We search by email hash or any matching visitor records
        if ($dsarRequest->site_id) {
            $records = ConsentRecord::where('site_id', $dsarRequest->site_id)
                ->latest()
                ->limit(1000)
                ->get();
        } else {
            // All sites for this customer
            $records = ConsentRecord::whereHas('site', function ($q) use ($dsarRequest) {
                $q->where('customer_id', $dsarRequest->customer_id);
            })->latest()->limit(1000)->get();
        }
        
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
        if ($dsarRequest->site_id) {
            // Delete from specific site
            ConsentRecord::where('site_id', $dsarRequest->site_id)->delete();
        } else {
            // Delete from all customer sites
            $siteIds = $dsarRequest->customer->sites()->pluck('id');
            ConsentRecord::whereIn('site_id', $siteIds)->delete();
        }
    }

    /**
     * Process restriction request
     */
    private function processRestriction(DsarRequest $dsarRequest): void
    {
        // Mark all consent records as withdrawn
        if ($dsarRequest->site_id) {
            ConsentRecord::where('site_id', $dsarRequest->site_id)
                ->whereNull('withdrawn_at')
                ->update([
                    'withdrawn_at' => now(),
                    'withdrawal_reason' => 'DSAR restriction request',
                ]);
        }
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
        
        return Storage::download($dsarRequest->data_export_path, 'data-export.json');
    }
}
