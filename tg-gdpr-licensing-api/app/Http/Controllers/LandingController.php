<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Lead;

class LandingController extends Controller
{
    /**
     * Display the landing page.
     */
    public function index()
    {
        return view('landing.index');
    }

    /**
     * Show the pricing page.
     */
    public function pricing()
    {
        $plans = [
            [
                'name' => 'Starter',
                'price' => 49,
                'interval' => 'year',
                'sites' => 1,
                'features' => [
                    'Single site license',
                    'GDPR compliant cookie banner',
                    'Customizable design',
                    'Priority support',
                    'Free updates for 1 year',
                ],
                'stripe_price_id' => config('services.stripe.plans.starter'),
            ],
            [
                'name' => 'Professional',
                'price' => 99,
                'interval' => 'year',
                'sites' => 5,
                'popular' => true,
                'features' => [
                    'Up to 5 site licenses',
                    'GDPR compliant cookie banner',
                    'Advanced customization',
                    'Priority support',
                    'Free updates for 1 year',
                    'Commercial use',
                ],
                'stripe_price_id' => config('services.stripe.plans.professional'),
            ],
            [
                'name' => 'Agency',
                'price' => 199,
                'interval' => 'year',
                'sites' => 25,
                'features' => [
                    'Up to 25 site licenses',
                    'GDPR compliant cookie banner',
                    'White-label options',
                    'Dedicated support',
                    'Lifetime updates',
                    'Commercial use',
                    'Agency toolkit',
                ],
                'stripe_price_id' => config('services.stripe.plans.agency'),
            ],
        ];

        return view('landing.pricing', compact('plans'));
    }

    /**
     * Handle contact form submission.
     */
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        // Store lead
        Lead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'message' => $validated['message'],
            'source' => 'contact_form',
        ]);

        // Send notification email (implement later)
        // Mail::to(config('mail.from.address'))->send(new ContactFormSubmitted($validated));

        return back()->with('success', 'Thank you! We\'ll get back to you within 24 hours.');
    }

    /**
     * Download free version.
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Store lead
        Lead::create([
            'email' => $validated['email'],
            'source' => 'free_download',
        ]);

        // Return download link
        return redirect()->away('https://wordpress.org/plugins/tg-gdpr-cookie-banner/')
            ->with('success', 'Download started! Check your email for the free version.');
    }
}
