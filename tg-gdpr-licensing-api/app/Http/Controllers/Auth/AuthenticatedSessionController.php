<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Sends the user to their role-appropriate landing page after auth.
     * `intended()` still wins if the user was bounced from a deep link —
     * e.g. a customer who tried /customer/sites pre-login still lands there.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended($this->postLoginPath($request->user()));
    }

    /**
     * Where to send a freshly-authed user when there's no `intended` URL.
     */
    private function postLoginPath($user): string
    {
        return $user?->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('customer.dashboard', absolute: false);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
