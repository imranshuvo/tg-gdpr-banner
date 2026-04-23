@extends('layouts.admin')

@section('title', 'Mail Settings')
@section('page-title', 'Mail Settings')

@section('content')
<div class="max-w-4xl">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Current Config --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Configuration</h2>
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Mail Driver</dt>
                <dd class="font-mono font-medium text-gray-900 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $config['driver'] === 'log' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                        {{ $config['driver'] }}
                    </span>
                    @if ($config['driver'] === 'log')
                        <span class="ml-2 text-yellow-700 text-xs">Emails are written to the log file only — not delivered.</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">From Address</dt>
                <dd class="font-medium text-gray-900 mt-1">{{ $config['from_address'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">From Name</dt>
                <dd class="font-medium text-gray-900 mt-1">{{ $config['from_name'] ?: '—' }}</dd>
            </div>
            @if ($config['driver'] === 'smtp')
            <div>
                <dt class="text-gray-500">SMTP Host</dt>
                <dd class="font-medium text-gray-900 mt-1">{{ $config['host'] ?: '—' }}:{{ $config['port'] }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Provider Guide --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Provider Configuration Guide</h2>
        <p class="text-sm text-gray-600 mb-6">Edit your <code class="bg-gray-100 px-1 rounded">.env</code> file on the server and set <code class="bg-gray-100 px-1 rounded">MAIL_MAILER</code> to one of the options below. Restart the application after making changes.</p>

        <div class="space-y-4">
            @foreach ([
                ['name' => 'Resend', 'driver' => 'resend', 'badge' => 'Recommended', 'vars' => "MAIL_MAILER=resend\nRESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxxxxx"],
                ['name' => 'SMTP', 'driver' => 'smtp', 'vars' => "MAIL_MAILER=smtp\nMAIL_HOST=smtp.sendgrid.net\nMAIL_PORT=587\nMAIL_USERNAME=apikey\nMAIL_PASSWORD=SG.xxxxxxxxxx\nMAIL_SCHEME=tls"],
                ['name' => 'Mailgun', 'driver' => 'mailgun', 'vars' => "MAIL_MAILER=mailgun\nMAILGUN_DOMAIN=mg.yourdomain.com\nMAILGUN_SECRET=key-xxxxxxxx\nMAILGUN_ENDPOINT=api.eu.mailgun.net"],
                ['name' => 'Amazon SES', 'driver' => 'ses', 'vars' => "MAIL_MAILER=ses\nAWS_ACCESS_KEY_ID=xxxx\nAWS_SECRET_ACCESS_KEY=xxxx\nAWS_DEFAULT_REGION=eu-west-1"],
                ['name' => 'Postmark', 'driver' => 'postmark', 'vars' => "MAIL_MAILER=postmark\nPOSTMARK_TOKEN=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"],
            ] as $provider)
            <details class="border border-gray-200 rounded-lg {{ $config['driver'] === $provider['driver'] ? 'border-indigo-300 bg-indigo-50' : '' }}" {{ $config['driver'] === $provider['driver'] ? 'open' : '' }}>
                <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-900 flex items-center gap-2">
                    {{ $provider['name'] }}
                    @if ($config['driver'] === $provider['driver'])
                        <span class="text-xs text-indigo-600 font-semibold">Active</span>
                    @endif
                    @if (isset($provider['badge']))
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ $provider['badge'] }}</span>
                    @endif
                </summary>
                <div class="px-4 pb-4">
                    <pre class="mt-2 bg-gray-900 text-green-400 text-xs rounded p-3 overflow-x-auto">{{ $provider['vars'] }}</pre>
                </div>
            </details>
            @endforeach
        </div>
    </div>

    {{-- Test Email --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Send Test Email</h2>
        <p class="text-sm text-gray-600 mb-4">Send a test email to verify the current mail configuration is working correctly.</p>

        <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label for="recipient" class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                <input type="email" name="recipient" id="recipient" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="you@example.com"
                    value="{{ auth()->user()->email }}">
            </div>
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                Send Test
            </button>
        </form>
    </div>

</div>
@endsection
