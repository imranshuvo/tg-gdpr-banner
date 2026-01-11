<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LicenseController extends Controller
{
    public function __construct(
        private LicenseService $licenseService
    ) {}

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'domain' => 'required|string',
            'site_url' => 'required|url',
        ]);

        $result = $this->licenseService->activate(
            $validated['license_key'],
            $validated['domain'],
            $validated['site_url']
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function deactivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'domain' => 'required|string',
        ]);

        $result = $this->licenseService->deactivate(
            $validated['license_key'],
            $validated['domain']
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'domain' => 'required|string',
        ]);

        $result = $this->licenseService->verify(
            $validated['license_key'],
            $validated['domain']
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
