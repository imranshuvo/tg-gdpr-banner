<?php

namespace App\Http\Controllers\Api;

use App\Mail\DsarVerificationMail;
use App\Mail\NewDsarRequestMail;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\DsarRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        
        $normalizedPayload = [
            'request_type' => $request->input('request_type', $request->input('type')),
            'requester_email' => $request->input('requester_email', $request->input('email')),
            'requester_name' => trim((string) $request->input(
                'requester_name',
                implode(' ', array_filter([
                    $request->input('first_name'),
                    $request->input('last_name'),
                ]))
            )),
            'requester_phone' => $request->input('requester_phone', $request->input('phone')),
            'additional_info' => $request->input('additional_info', $request->input('message')),
            'visitor_hash' => $request->input('visitor_hash'),
        ];

        $validated = validator($normalizedPayload, [
            'request_type' => 'required|in:access,erasure,rectification,portability,restriction,objection',
            'requester_email' => 'required|email|max:255',
            'requester_name' => 'nullable|string|max:255',
            'requester_phone' => 'nullable|string|max:20',
            'additional_info' => 'nullable|string|max:2000',
            'visitor_hash' => 'nullable|string|size:64|regex:/^[a-f0-9]{64}$/i',
        ])->validate();
        
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
    public function download(Request $request, string $token)
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

        if (!Storage::exists($dsarRequest->data_export_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Export file is no longer available',
            ], 404);
        }
        
        $dsarRequest->increment('download_count');

        return Storage::download(
            $dsarRequest->data_export_path,
            sprintf('dsar-%s-request-%d.json', $dsarRequest->request_type, $dsarRequest->id)
        );
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(DsarRequest $dsarRequest): void
    {
        $verificationUrl = url('/api/v1/dsar/verify/' . $dsarRequest->verification_token);

        Mail::to($dsarRequest->requester_email)->send(new DsarVerificationMail($dsarRequest, $verificationUrl));
        
        $dsarRequest->update(['verification_sent_at' => now()]);
    }

    /**
     * Notify admin of new verified request
     */
    private function notifyAdminOfNewRequest(DsarRequest $dsarRequest): void
    {
        $recipients = $this->getAdminNotificationRecipients();

        if (empty($recipients)) {
            return;
        }

        $adminUrl = url('/admin/dsar/' . $dsarRequest->id);

        foreach ($recipients as $email) {
            Mail::to($email)->send(new NewDsarRequestMail($dsarRequest, $adminUrl));
        }
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

    /**
     * Get the configured admin notification recipients.
     *
     * @return array<int, string>
     */
    private function getAdminNotificationRecipients(): array
    {
        $configRecipients = array_filter((array) config('app.admin_emails', []));
        $adminUsers = User::query()
            ->where('role', 'admin')
            ->pluck('email')
            ->filter()
            ->all();

        return array_values(array_unique(array_merge($configRecipients, $adminUsers)));
    }
}
