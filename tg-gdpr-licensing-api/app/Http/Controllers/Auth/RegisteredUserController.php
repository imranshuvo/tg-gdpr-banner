<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Atomic: a User without a linked Customer cannot complete checkout
        // (StripeProvider::onCheckoutCompleted bails on null customer_id and
        // the customer would pay without ever receiving a License). Bundle
        // user + customer creation so we never produce that broken half-state.
        $user = DB::transaction(function () use ($request) {
            $customer = Customer::create([
                'name'  => $request->name,
                'email' => $request->email,
            ]);

            return User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'customer_id' => $customer->id,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

            // Send welcome email (swallowed on failure so registration still succeeds)
            try {
                Mail::to($user->email)->queue(new WelcomeMail($user));
            } catch (\Throwable) {}

        return redirect(route('dashboard', absolute: false));
    }
}
