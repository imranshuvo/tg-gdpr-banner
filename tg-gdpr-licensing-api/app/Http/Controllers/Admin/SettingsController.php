<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    /**
     * Show the mail settings page.
     */
    public function mail()
    {
        $config = [
            'driver'       => config('mail.default', 'log'),
            'from_address' => config('mail.from.address', ''),
            'from_name'    => config('mail.from.name', config('app.name')),
            'host'         => config('mail.mailers.smtp.host', ''),
            'port'         => config('mail.mailers.smtp.port', 587),
        ];

        return view('admin.settings.mail', compact('config'));
    }

    /**
     * Send a test email to verify the current mail configuration.
     */
    public function testMail(Request $request)
    {
        $request->validate([
            'recipient' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::raw(
                'This is a test email from ' . config('app.name') . ' to confirm your mail configuration is working correctly.',
                function ($message) use ($request) {
                    $message->to($request->recipient)
                            ->subject('[' . config('app.name') . '] Mail configuration test');
                }
            );

            return back()->with('success', 'Test email sent to ' . $request->recipient . '. Check the inbox.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
