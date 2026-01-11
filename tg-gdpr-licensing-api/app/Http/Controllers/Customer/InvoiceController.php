<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Logging\ActivityLogger;

class InvoiceController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Display all invoices.
     */
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Get all invoices from Stripe
        $invoices = [];
        
        if ($user->hasStripeId()) {
            try {
                $invoices = $user->invoices();
            } catch (\Exception $e) {
                // Handle Stripe error gracefully
            }
        }

        return view('customer.invoices.index', compact('customer', 'invoices'));
    }

    /**
     * Download invoice PDF.
     */
    public function download(Request $request, string $invoiceId)
    {
        $user = Auth::user();
        $customer = $user->customer;

        // Log invoice download
        $this->activityLogger->logPayment(
            'invoice_downloaded',
            $customer,
            [
                'invoice_id' => $invoiceId,
                'customer_id' => $customer->id,
            ]
        );

        try {
            return $user->downloadInvoice($invoiceId, [
                'vendor' => config('app.name'),
                'product' => 'TG GDPR Banner License',
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('customer.invoices.index')
                ->with('error', 'Unable to download invoice. Please try again or contact support.');
        }
    }
}
