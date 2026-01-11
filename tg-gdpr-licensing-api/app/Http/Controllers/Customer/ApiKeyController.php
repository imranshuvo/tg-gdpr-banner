<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\Logging\ActivityLogger;
use App\Models\SystemSetting;

class ApiKeyController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Display API keys management page.
     */
    public function index()
    {
        $customer = Auth::user()->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Get or create API key for customer
        $apiKey = SystemSetting::get("customer_{$customer->id}_api_key");
        $apiKeyMasked = $apiKey ? $this->maskApiKey($apiKey) : null;
        $apiKeyCreatedAt = SystemSetting::get("customer_{$customer->id}_api_key_created_at");
        $lastUsedAt = SystemSetting::get("customer_{$customer->id}_api_key_last_used");

        return view('customer.api-keys.index', compact(
            'customer',
            'apiKey',
            'apiKeyMasked',
            'apiKeyCreatedAt',
            'lastUsedAt'
        ));
    }

    /**
     * Generate a new API key.
     */
    public function generate(Request $request)
    {
        $customer = Auth::user()->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Generate new API key
        $apiKey = 'tg_' . Str::random(40);

        // Store API key (encrypted automatically by SystemSetting)
        SystemSetting::set("customer_{$customer->id}_api_key", $apiKey);
        SystemSetting::set("customer_{$customer->id}_api_key_created_at", now()->toDateTimeString());

        // Log API key generation
        $this->activityLogger->logSecurity(
            'api_key_generated',
            $customer,
            [
                'customer_id' => $customer->id,
                'ip' => $request->ip(),
            ]
        );

        return redirect()
            ->route('customer.api-keys.index')
            ->with('success', 'New API key generated successfully!')
            ->with('new_api_key', $apiKey); // Show once, then mask
    }

    /**
     * Revoke the API key.
     */
    public function revoke(Request $request)
    {
        $customer = Auth::user()->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Delete API key settings
        SystemSetting::where('key', "customer_{$customer->id}_api_key")->delete();
        SystemSetting::where('key', "customer_{$customer->id}_api_key_created_at")->delete();
        SystemSetting::where('key', "customer_{$customer->id}_api_key_last_used")->delete();

        // Log API key revocation
        $this->activityLogger->logSecurity(
            'api_key_revoked',
            $customer,
            [
                'customer_id' => $customer->id,
                'ip' => $request->ip(),
            ]
        );

        return redirect()
            ->route('customer.api-keys.index')
            ->with('success', 'API key revoked successfully!');
    }

    /**
     * Mask API key for display (show first 10 and last 4 characters).
     */
    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 14) {
            return $apiKey;
        }

        $start = substr($apiKey, 0, 10);
        $end = substr($apiKey, -4);
        $masked = str_repeat('*', strlen($apiKey) - 14);

        return $start . $masked . $end;
    }
}
