<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Logging\ActivityLogger;

class SubscriptionController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Display subscription management page.
     */
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer profile associated with this account.');
        }

        // Get active subscription
        $subscription = $user->subscriptions()->active()->first();
        
        // Get all subscriptions (including cancelled/expired)
        $allSubscriptions = $user->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payment methods
        $paymentMethods = $user->hasDefaultPaymentMethod() 
            ? [$user->defaultPaymentMethod()] 
            : [];

        // Get upcoming invoice if subscription exists
        $upcomingInvoice = null;
        if ($subscription && !$subscription->cancelled()) {
            try {
                $upcomingInvoice = $user->upcomingInvoice();
            } catch (\Exception $e) {
                // No upcoming invoice
            }
        }

        return view('customer.subscriptions.index', compact(
            'customer',
            'subscription',
            'allSubscriptions',
            'paymentMethods',
            'upcomingInvoice'
        ));
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $subscription = $user->subscriptions()->active()->first();

        if (!$subscription) {
            return redirect()
                ->route('customer.subscriptions.index')
                ->with('error', 'No active subscription found.');
        }

        // Cancel at period end
        $subscription->cancel();

        // Log cancellation
        $this->activityLogger->logPayment(
            'subscription_cancelled',
            $customer,
            [
                'subscription_id' => $subscription->stripe_id,
                'ends_at' => $subscription->ends_at,
            ]
        );

        return redirect()
            ->route('customer.subscriptions.index')
            ->with('success', 'Subscription cancelled. You will have access until ' . $subscription->ends_at->format('F j, Y'));
    }

    /**
     * Resume subscription.
     */
    public function resume(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $subscription = $user->subscriptions()->active()->first();

        if (!$subscription || !$subscription->cancelled()) {
            return redirect()
                ->route('customer.subscriptions.index')
                ->with('error', 'No cancelled subscription found to resume.');
        }

        // Resume subscription
        $subscription->resume();

        // Log resumption
        $this->activityLogger->logPayment(
            'subscription_resumed',
            $customer,
            [
                'subscription_id' => $subscription->stripe_id,
            ]
        );

        return redirect()
            ->route('customer.subscriptions.index')
            ->with('success', 'Subscription resumed successfully!');
    }
}
