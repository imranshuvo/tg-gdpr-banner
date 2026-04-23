<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Logging\ActivityLogger;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Display all licenses for the authenticated customer.
     */
    public function index()
    {
        $customer = Auth::user()->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        $licenses = $customer->licenses()
            ->with('activations')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.licenses.index', compact('licenses', 'customer'));
    }

    /**
     * Display a specific license.
     */
    public function show(License $license)
    {
        $customer = Auth::user()->customer;

        // Ensure the license belongs to this customer
        if ($license->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to this license.');
        }

        $license->load('activations');

        // Log license view
        $this->activityLogger->logLicense(
            'license_viewed',
            $license,
            [
                'license_key' => $license->license_key,
                'customer_id' => $customer->id,
            ]
        );

        return view('customer.licenses.show', compact('license', 'customer'));
    }

    /**
     * Download license key as text file.
     */
    public function download(License $license)
    {
        $customer = Auth::user()->customer;

        // Ensure the license belongs to this customer
        if ($license->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to this license.');
        }

        // Log download
        $this->activityLogger->logLicense(
            'license_downloaded',
            $license,
            [
                'license_key' => $license->license_key,
                'customer_id' => $customer->id,
            ]
        );

        $productName = config('app.name', 'Cookiely');
        $heading = $productName . ' License Key';

        $content = $heading . "\n";
        $content .= str_repeat('=', strlen($heading)) . "\n\n";
        $content .= "Customer: {$customer->name}\n";
        $content .= "Email: {$customer->email}\n";
        $content .= "License Key: {$license->license_key}\n";
        $content .= "Plan: {$license->plan}\n";
        $content .= "Max Activations: {$license->max_activations}\n";
        $content .= "Expires: {$license->expires_at->format('Y-m-d')}\n";
        $content .= "Status: {$license->status}\n\n";
        $content .= "Instructions:\n";
        $content .= "1. Copy the license key above\n";
        $content .= "2. Open the Cookiely integration for your site\n";
        $content .= "3. Navigate to the license settings and enter the key\n";
        $content .= "4. Click 'Activate License'\n\n";
        $content .= "Need help? Contact support at support@example.com\n";

        $filename = Str::slug($productName) . "-license-{$license->license_key}.txt";

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
