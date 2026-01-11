<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\DsarRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class DsarController extends Controller
{
    /**
     * Submit a DSAR request (from frontend form)
     */
    public function submit(Request $request): JsonResponse
    {
        $site = $this->validateSiteToken($request);
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid site token',
            ], 401);
        }
        
        $validated = $request->validate([
            'request_type' => 'required|in:access,erasure,rectification,portability,restriction,objection',
            'requester_email' => 'required|email|max:255',
            'requester_name' => 'nullable|string|max:255',
            'requester_phone' => 'nullable|string|max:20',
            'additional_info' => 'nullable|string|max:2000',
        ]);
        
        $dsarRequest = DsarRequest::create(array_merge($validated, [
            'site_id' => $site->id,
            'customer_id' => $site->customer_id,
            'status' => 'pending_verification',
        ]));
        
        // Send verification email
        $this->sendVerificationEmail($dsarRequest);
        
        return response()->json([
            'success' => true,
            'message' => 'Request submitted. Please check your email to verify.',
            'request_id' => $dsarRequest->id,
        ]);
    }

    /**
     * Verify a DSAR request via email token
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        $dsarRequest = DsarRequest::where('verification_token', $token)
            ->where('status', 'pending_verification')
            ->first();
        
        if (!$dsarRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification token',
            ], 404);
        }
        
        // Check if token is not too old (24 hours)
        if ($dsarRequest->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Verification token has expired. Please submit a new request.',
            ], 410);
        }
        
        $dsarRequest->verify();
        
        // Notify admin
        $this->notifyAdminOfNewRequest($dsarRequest);
        
        return response()->json([
            'success' => true,
            'message' => 'Request verified. We will process your request within 30 days.',
        ]);
    }

    /**
     * Check status of a DSAR request
     */
    public function status(Request $request, string $token): JsonResponse
    {
        $dsarRequest = DsarRequest::where('verification_token', $token)->first();
        
        if (!$dsarRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }
        
        $response = [
            'success' => true,
            'data' => [
                'request_type' => $dsarRequest->request_type,
                'status' => $dsarRequest->status,
                'submitted_at' => $dsarRequest->created_at->toIso8601String(),
                'due_date' => $dsarRequest->due_date?->toIso8601String(),
                'completed_at' => $dsarRequest->completed_at?->toIso8601String(),
            ],
        ];
        
        // Include download link if completed with export
        if ($dsarRequest->status === 'completed' && $dsarRequest->data_export_path) {
            $response['data']['download_available'] = true;
            $response['data']['download_expires_at'] = $dsarRequest->export_expires_at?->toIso8601String();
        }
        
        return response()->json($response);
    }

    /**
     * Download data export (for portability/access requests)
     */
    public function download(Request $request, string $token): JsonResponse
    {
        $dsarRequest = DsarRequest::where('verification_token', $token)
            ->where('status', 'completed')
            ->whereNotNull('data_export_path')
            ->first();
        
        if (!$dsarRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Export not found or not ready',
            ], 404);
        }
        
        if ($dsarRequest->export_expires_at && $dsarRequest->export_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Export has expired',
            ], 410);
        }
        
        // Return download URL (actual download handled by storage)
        $dsarRequest->increment('download_count');
        
        return response()->json([
            'success' => true,
            'download_url' => route('api.dsar.download-file', ['token' => $token]),
        ]);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(DsarRequest $dsarRequest): void
    {
        // In production, use proper mail template
        $verificationUrl = config('app.url') . '/api/v1/dsar/verify/' . $dsarRequest->verification_token;
        
        // TODO: Send actual email
        // Mail::to($dsarRequest->requester_email)->send(new DsarVerificationMail($dsarRequest, $verificationUrl));
        
        $dsarRequest->update(['verification_sent_at' => now()]);
    }

    /**
     * Notify admin of new verified request
     */
    private function notifyAdminOfNewRequest(DsarRequest $dsarRequest): void
    {
        // TODO: Send notification to admins
        // Notification::send(User::admins()->get(), new NewDsarRequestNotification($dsarRequest));
    }

    /**
     * Validate site token
     */
    private function validateSiteToken(Request $request): ?Site
    {
        $token = $request->header('X-Site-Token') ?? $request->input('site_token');
        
        if (!$token) {
            return null;
        }
        
        return Site::where('site_token', $token)->first();
    }
}
