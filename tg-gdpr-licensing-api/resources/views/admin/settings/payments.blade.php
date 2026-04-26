@extends('layouts.admin')

@section('title', 'Payment Settings')
@section('page-title', 'Payment Settings')

@section('content')
<div class="max-w-4xl space-y-6">

    <p class="text-sm text-gray-600">
        Configure payment providers. Test and live keys are stored separately — flip the mode toggle to switch which set is active.
        Secrets are stored encrypted; saved values are never re-shown. Leave a field blank to keep the existing secret in place.
    </p>

    @foreach ($providers as $p)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form method="POST" action="{{ route('admin.settings.payments.update', $p['name']) }}" class="p-6 space-y-6">
                @csrf

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $p['label'] }}</h2>
                        @if ($p['enabled'] && $p['configured'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active · {{ ucfirst($p['mode']) }}
                            </span>
                        @elseif ($p['enabled'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Enabled · Not configured
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                Disabled
                            </span>
                        @endif
                    </div>

                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" {{ $p['enabled'] ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Enabled</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Active mode</label>
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        @foreach (['test', 'live'] as $m)
                            <label class="px-4 py-2 text-sm font-medium border border-gray-300 cursor-pointer
                                {{ $loop->first ? 'rounded-l-md' : 'rounded-r-md -ml-px' }}
                                {{ $p['mode'] === $m ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                                <input type="radio" name="mode" value="{{ $m }}" class="sr-only" {{ $p['mode'] === $m ? 'checked' : '' }}>
                                {{ ucfirst($m) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @if ($p['name'] === 'frisbii')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API endpoint <span class="text-gray-400 text-xs">(optional override)</span></label>
                        <input type="url" name="endpoint" value="{{ $p['endpoint'] }}" placeholder="https://api.frisbii.com"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                @endif

                @foreach (['test', 'live'] as $env)
                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="px-2 text-sm font-semibold text-gray-700">{{ ucfirst($env) }} credentials</legend>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Publishable key
                                    @if ($p['creds'][$env]['public_set'])
                                        <span class="ml-1 text-green-700">· {{ $p['creds'][$env]['public_hint'] }}</span>
                                    @endif
                                </label>
                                <input type="text" name="{{ $env }}_public" autocomplete="off"
                                       placeholder="{{ $p['creds'][$env]['public_set'] ? '(leave blank to keep)' : ($p['name'] === 'stripe' ? "pk_{$env}_…" : 'API key') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Secret key
                                    @if ($p['creds'][$env]['secret_set'])
                                        <span class="ml-1 text-green-700">· already set</span>
                                    @endif
                                </label>
                                <input type="password" name="{{ $env }}_secret" autocomplete="new-password"
                                       placeholder="{{ $p['creds'][$env]['secret_set'] ? '(leave blank to keep)' : ($p['name'] === 'stripe' ? "sk_{$env}_…" : 'Secret') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Webhook signing secret
                                    @if ($p['creds'][$env]['webhook_set'])
                                        <span class="ml-1 text-green-700">· already set</span>
                                    @endif
                                </label>
                                <input type="password" name="{{ $env }}_webhook" autocomplete="new-password"
                                       placeholder="{{ $p['creds'][$env]['webhook_set'] ? '(leave blank to keep)' : ($p['name'] === 'stripe' ? "whsec_…" : 'Signing secret') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
                                <p class="mt-1 text-xs text-gray-500">
                                    Webhook URL: <code class="text-gray-700">{{ url("/webhooks/payments/{$p['name']}") }}</code>
                                </p>
                            </div>
                        </div>
                    </fieldset>
                @endforeach

                <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        Save {{ $p['label'] }}
                    </button>

                    @if ($p['configured'])
                        <button type="submit" formaction="{{ route('admin.settings.payments.test', $p['name']) }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md">
                            Test connection
                        </button>
                    @endif
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
