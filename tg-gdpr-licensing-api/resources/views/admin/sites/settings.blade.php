@extends('layouts.admin')

@section('title', 'Site Settings: ' . $site->domain)
@section('page-title', 'Banner & Settings — ' . $site->domain)

@section('content')
@php
    $geoMode = old('geo_targeting_mode', $site->getGeoTargetingMode());
    $selectedGeoCountries = old('geo_countries', $site->getGeoTargetingMode() === 'selected' ? ($site->geo_countries ?? []) : []);
    $availableGeoCountries = [
        'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
        'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
        'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
        'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
        'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
        'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
        'SI' => 'Slovenia', 'ES' => 'Spain', 'SE' => 'Sweden', 'GB' => 'United Kingdom',
        'IS' => 'Iceland', 'LI' => 'Liechtenstein', 'NO' => 'Norway', 'CH' => 'Switzerland',
    ];
    $categories = ['necessary', 'functional', 'analytics', 'marketing'];
    $defaultLabels = ['Essential', 'Functional', 'Analytics', 'Marketing'];
    $defaultDescriptions = [
        'These cookies are essential for the website to function properly.',
        'These cookies enable personalized features and functionality.',
        'These cookies help us understand how visitors interact with the website.',
        'These cookies are used to deliver personalized advertisements.',
    ];
    $gcmDefaults = $site->settings->gcm_default_state ?? [
        'ad_storage' => 'denied', 'analytics_storage' => 'denied',
        'ad_user_data' => 'denied', 'ad_personalization' => 'denied',
        'functionality_storage' => 'denied', 'personalization_storage' => 'denied',
        'security_storage' => 'granted',
    ];
@endphp

<div class="space-y-6">
    <nav class="text-sm">
        <a href="{{ route('admin.sites.index') }}" class="text-blue-600 hover:text-blue-800">Sites</a>
        <span class="text-gray-400 mx-2">/</span>
        <a href="{{ route('admin.sites.show', $site) }}" class="text-blue-600 hover:text-blue-800">{{ $site->domain }}</a>
        <span class="text-gray-400 mx-2">/</span>
        <span class="text-gray-700">Settings</span>
    </nav>

    <form method="POST" action="{{ route('admin.sites.settings.update', $site) }}">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Banner appearance --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-palette mr-2 text-gray-500"></i>Banner appearance</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <select name="banner_position" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @foreach (['bottom' => 'Bottom', 'top' => 'Top', 'bottom-left' => 'Bottom Left', 'bottom-right' => 'Bottom Right', 'center' => 'Center (Popup)'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ ($site->settings->banner_position ?? 'bottom') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Layout</label>
                            <select name="banner_layout" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @foreach (['bar' => 'Bar', 'box' => 'Box', 'popup' => 'Popup'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ ($site->settings->banner_layout ?? 'bar') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach ([
                            ['primary_color', 'Primary color', '#1e40af'],
                            ['accent_color',  'Accent color',  '#3b82f6'],
                            ['text_color',    'Text color',    '#1f2937'],
                            ['bg_color',      'Background color', '#ffffff'],
                        ] as [$name, $label, $default])
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                <input type="color" name="{{ $name }}" value="{{ $site->settings->{$name} ?? $default }}"
                                       class="w-full h-10 rounded-md border-gray-300 cursor-pointer">
                            </div>
                        @endforeach
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Button style</label>
                            <select name="button_style" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @foreach (['rounded' => 'Rounded', 'square' => 'Square', 'pill' => 'Pill'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ ($site->settings->button_style ?? 'rounded') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Banner content --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-comment-alt mr-2 text-gray-500"></i>Banner content</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                            <input type="text" name="heading" value="{{ $site->settings->heading ?? 'We value your privacy' }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea name="message" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ $site->settings->message ?? 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic.' }}</textarea>
                        </div>
                        @foreach ([
                            ['accept_all_text',       'Accept All button text',  'Accept All'],
                            ['reject_all_text',       'Reject All button text',  'Reject All'],
                            ['customize_text',        'Customize button text',   'Customize'],
                            ['save_preferences_text', 'Save Preferences text',   'Save Preferences'],
                        ] as [$name, $label, $default])
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                <input type="text" name="{{ $name }}" value="{{ $site->settings->{$name} ?? $default }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                        @endforeach
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Privacy policy URL</label>
                            <input type="url" name="privacy_policy_url" value="{{ $site->settings->privacy_policy_url ?? '' }}" placeholder="https://example.com/privacy-policy"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Privacy policy link text</label>
                            <input type="text" name="privacy_policy_text" value="{{ $site->settings->privacy_policy_text ?? 'Privacy Policy' }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Category labels --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-tags mr-2 text-gray-500"></i>Category labels &amp; descriptions</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach ($categories as $i => $category)
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 {{ $i < 3 ? 'pb-4 border-b border-gray-100' : '' }}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ ucfirst($category) }} label</label>
                                    <input type="text" name="category_labels[{{ $category }}]" value="{{ $site->settings->category_labels[$category] ?? $defaultLabels[$i] }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <input type="text" name="category_descriptions[{{ $category }}]" value="{{ $site->settings->category_descriptions[$category] ?? $defaultDescriptions[$i] }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Geo targeting --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-globe mr-2 text-gray-500"></i>Geo targeting</h3>
                    </div>
                    <div class="p-6" x-data="{ mode: '{{ $geoMode }}' }">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Banner scope</label>
                                <select name="geo_targeting_mode" x-model="mode"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('geo_targeting_mode') border-red-500 @enderror">
                                    <option value="all">All countries</option>
                                    <option value="eu">EU/EEA/UK/CH only</option>
                                    <option value="selected">Selected European countries only</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Where the CMP banner + regional consent defaults apply.</p>
                                @error('geo_targeting_mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div x-show="mode === 'selected'" x-cloak x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Selected countries</label>
                                <select name="geo_countries[]" multiple size="10"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @foreach ($availableGeoCountries as $code => $label)
                                        <option value="{{ $code }}" {{ in_array($code, $selectedGeoCountries, true) ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Cmd on Mac) to multi-select.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Behavior --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-sliders-h mr-2 text-gray-500"></i>Behavior</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ([
                            ['show_reject_all',    'Show "Reject All" button',     true],
                            ['show_close_button',  'Show close (X) button',        false],
                            ['reload_on_consent',  'Reload page after consent',    false],
                            ['respect_dnt',        'Respect "Do Not Track" header', false],
                        ] as [$name, $label, $default])
                            <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="{{ $name }}" value="1" {{ ($site->settings->{$name} ?? $default) ? 'checked' : '' }}
                                       class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                            </label>
                        @endforeach
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Consent expiry (days)</label>
                            <input type="number" name="consent_expiry_days" min="1" max="730" value="{{ $site->settings->consent_expiry_days ?? 365 }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Re-consent after (days)</label>
                            <input type="number" name="reconsent_days" min="1" max="730" value="{{ $site->settings->reconsent_days ?? 365 }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">GDPR recommends max 12 months.</p>
                        </div>
                    </div>
                </div>

                {{-- Script blocking --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-shield-alt mr-2 text-gray-500"></i>Script blocking</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="auto_block_scripts" value="1" {{ ($site->settings->auto_block_scripts ?? true) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Auto-block scripts</div>
                                <div class="text-xs text-gray-500">Block known tracking scripts until consent is given.</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="log_consents" value="1" {{ ($site->settings->log_consents ?? true) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Log consent records</div>
                                <div class="text-xs text-gray-500">Store consent records for GDPR compliance proof.</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- GCM v2 --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fab fa-google mr-2 text-gray-500"></i>Google Consent Mode v2</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i> Google Consent Mode v2 is required for Google Ads and Analytics in the EU since March 2024.
                        </div>
                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="gcm_wait_for_update" value="1" {{ ($site->settings->gcm_wait_for_update ?? true) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-900">Wait for consent before sending data</span>
                        </label>
                        <div class="md:max-w-xs">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Wait timeout (ms)</label>
                            <input type="number" name="gcm_wait_timeout_ms" min="0" max="5000" value="{{ $site->settings->gcm_wait_timeout_ms ?? 500 }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="pt-4 border-t border-gray-100">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Default consent state (before user interaction)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach (['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization', 'functionality_storage', 'personalization_storage', 'security_storage'] as $key)
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1 font-mono">{{ $key }}</label>
                                        <select name="gcm_default_state[{{ $key }}]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="denied"  {{ ($gcmDefaults[$key] ?? 'denied')  === 'denied'  ? 'selected' : '' }}>Denied</option>
                                            <option value="granted" {{ ($gcmDefaults[$key] ?? '')       === 'granted' ? 'selected' : '' }}>Granted</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Custom code --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900"><i class="fas fa-code mr-2 text-gray-500"></i>Custom code</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Custom CSS</label>
                            <textarea name="custom_css" rows="4" placeholder=".tg-gdpr-banner { /* your styles */ }"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">{{ $site->settings->custom_css ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Custom JavaScript</label>
                            <textarea name="custom_js" rows="4" placeholder="// Runs after consent is saved"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">{{ $site->settings->custom_js ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md mb-3">
                        <i class="fas fa-check mr-2"></i> Save all settings
                    </button>
                    <a href="{{ route('admin.sites.show', $site) }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md">Cancel</a>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Tips</h3>
                    </div>
                    <ul class="p-6 list-disc pl-5 space-y-2 text-sm text-gray-700">
                        <li>Changes take effect immediately on the site.</li>
                        <li>GDPR recommends re-consent every 12 months at most.</li>
                        <li>"Reject All" button is required in some EU countries.</li>
                        <li>Google Consent Mode v2 is mandatory for EU Google Ads.</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
