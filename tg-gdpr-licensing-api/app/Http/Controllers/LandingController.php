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
        return redirect('/#pricing');
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

        return back()->with('success', 'Thanks. We received your request and will send free access details to your email.');
    }

    /**
     * Switch UI locale and persist it in the session.
     */
    public function switchLocale(Request $request, string $locale)
    {
        $supported = array_keys(config('locales.supported', []));

        if (in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);
        }

        return back();
    }

    /**
     * Show the Privacy Policy page.
     */
    public function privacyPolicy()
    {
        return view('legal.privacy-policy');
    }

    /**
     * Show the Terms of Service page.
     */
    public function termsOfService()
    {
        return view('legal.terms-of-service');
    }
}
