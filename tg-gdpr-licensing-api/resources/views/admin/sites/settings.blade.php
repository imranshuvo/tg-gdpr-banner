@extends('layouts.admin')

@section('title', 'Site Settings: ' . $site->domain)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('admin.sites.index') }}">Sites</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sites.show', $site) }}">{{ $site->domain }}</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Banner & Settings</h1>
        <p class="text-muted">Configure all CMP settings for {{ $site->domain }}</p>
    </div>

    <form method="POST" action="{{ route('admin.sites.settings.update', $site) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- Banner Appearance --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Banner Appearance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Position</label>
                                <select name="banner_position" class="form-select">
                                    <option value="bottom" {{ ($site->settings->banner_position ?? 'bottom') == 'bottom' ? 'selected' : '' }}>Bottom</option>
                                    <option value="top" {{ ($site->settings->banner_position ?? '') == 'top' ? 'selected' : '' }}>Top</option>
                                    <option value="bottom-left" {{ ($site->settings->banner_position ?? '') == 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                                    <option value="bottom-right" {{ ($site->settings->banner_position ?? '') == 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                                    <option value="center" {{ ($site->settings->banner_position ?? '') == 'center' ? 'selected' : '' }}>Center (Popup)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Layout</label>
                                <select name="banner_layout" class="form-select">
                                    <option value="bar" {{ ($site->settings->banner_layout ?? 'bar') == 'bar' ? 'selected' : '' }}>Bar</option>
                                    <option value="box" {{ ($site->settings->banner_layout ?? '') == 'box' ? 'selected' : '' }}>Box</option>
                                    <option value="popup" {{ ($site->settings->banner_layout ?? '') == 'popup' ? 'selected' : '' }}>Popup</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Primary Color</label>
                                <input type="color" name="primary_color" class="form-control form-control-color w-100" value="{{ $site->settings->primary_color ?? '#1e40af' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Accent Color</label>
                                <input type="color" name="accent_color" class="form-control form-control-color w-100" value="{{ $site->settings->accent_color ?? '#3b82f6' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Text Color</label>
                                <input type="color" name="text_color" class="form-control form-control-color w-100" value="{{ $site->settings->text_color ?? '#1f2937' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="bg_color" class="form-control form-control-color w-100" value="{{ $site->settings->bg_color ?? '#ffffff' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Button Style</label>
                                <select name="button_style" class="form-select">
                                    <option value="rounded" {{ ($site->settings->button_style ?? 'rounded') == 'rounded' ? 'selected' : '' }}>Rounded</option>
                                    <option value="square" {{ ($site->settings->button_style ?? '') == 'square' ? 'selected' : '' }}>Square</option>
                                    <option value="pill" {{ ($site->settings->button_style ?? '') == 'pill' ? 'selected' : '' }}>Pill</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Banner Content --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Banner Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Heading</label>
                                <input type="text" name="heading" class="form-control" value="{{ $site->settings->heading ?? 'We value your privacy' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="3">{{ $site->settings->message ?? 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic.' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Accept All Button Text</label>
                                <input type="text" name="accept_all_text" class="form-control" value="{{ $site->settings->accept_all_text ?? 'Accept All' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reject All Button Text</label>
                                <input type="text" name="reject_all_text" class="form-control" value="{{ $site->settings->reject_all_text ?? 'Reject All' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customize Button Text</label>
                                <input type="text" name="customize_text" class="form-control" value="{{ $site->settings->customize_text ?? 'Customize' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Save Preferences Button Text</label>
                                <input type="text" name="save_preferences_text" class="form-control" value="{{ $site->settings->save_preferences_text ?? 'Save Preferences' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Privacy Policy URL</label>
                                <input type="url" name="privacy_policy_url" class="form-control" value="{{ $site->settings->privacy_policy_url ?? '' }}" placeholder="https://example.com/privacy-policy">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Privacy Policy Link Text</label>
                                <input type="text" name="privacy_policy_text" class="form-control" value="{{ $site->settings->privacy_policy_text ?? 'Privacy Policy' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Category Labels --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Category Labels & Descriptions</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $categories = ['necessary', 'functional', 'analytics', 'marketing'];
                            $defaultLabels = ['Essential', 'Functional', 'Analytics', 'Marketing'];
                            $defaultDescriptions = [
                                'These cookies are essential for the website to function properly.',
                                'These cookies enable personalized features and functionality.',
                                'These cookies help us understand how visitors interact with the website.',
                                'These cookies are used to deliver personalized advertisements.',
                            ];
                        @endphp
                        @foreach($categories as $index => $category)
                            <div class="row g-3 mb-3 pb-3 {{ $index < 3 ? 'border-bottom' : '' }}">
                                <div class="col-md-3">
                                    <label class="form-label">{{ ucfirst($category) }} Label</label>
                                    <input type="text" name="category_labels[{{ $category }}]" class="form-control" 
                                           value="{{ $site->settings->category_labels[$category] ?? $defaultLabels[$index] }}">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">{{ ucfirst($category) }} Description</label>
                                    <input type="text" name="category_descriptions[{{ $category }}]" class="form-control" 
                                           value="{{ $site->settings->category_descriptions[$category] ?? $defaultDescriptions[$index] }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Geo Targeting --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Geo Targeting</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $geoMode = old('geo_targeting_mode', $site->getGeoTargetingMode());
                            $selectedGeoCountries = old('geo_countries', $site->getGeoTargetingMode() === 'selected' ? ($site->geo_countries ?? []) : []);
                            $availableGeoCountries = [
                                'AT' => 'Austria',
                                'BE' => 'Belgium',
                                'BG' => 'Bulgaria',
                                'HR' => 'Croatia',
                                'CY' => 'Cyprus',
                                'CZ' => 'Czech Republic',
                                'DK' => 'Denmark',
                                'EE' => 'Estonia',
                                'FI' => 'Finland',
                                'FR' => 'France',
                                'DE' => 'Germany',
                                'GR' => 'Greece',
                                'HU' => 'Hungary',
                                'IE' => 'Ireland',
                                'IT' => 'Italy',
                                'LV' => 'Latvia',
                                'LT' => 'Lithuania',
                                'LU' => 'Luxembourg',
                                'MT' => 'Malta',
                                'NL' => 'Netherlands',
                                'PL' => 'Poland',
                                'PT' => 'Portugal',
                                'RO' => 'Romania',
                                'SK' => 'Slovakia',
                                'SI' => 'Slovenia',
                                'ES' => 'Spain',
                                'SE' => 'Sweden',
                                'GB' => 'United Kingdom',
                                'IS' => 'Iceland',
                                'LI' => 'Liechtenstein',
                                'NO' => 'Norway',
                                'CH' => 'Switzerland',
                            ];
                        @endphp
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Banner Scope</label>
                                <select name="geo_targeting_mode" class="form-select @error('geo_targeting_mode') is-invalid @enderror">
                                    <option value="all" {{ $geoMode === 'all' ? 'selected' : '' }}>All countries</option>
                                    <option value="eu" {{ $geoMode === 'eu' ? 'selected' : '' }}>EU/EEA/UK/CH only</option>
                                    <option value="selected" {{ $geoMode === 'selected' ? 'selected' : '' }}>Selected European countries only</option>
                                </select>
                                @error('geo_targeting_mode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Use this to decide where the CMP banner and regional consent defaults apply.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Selected Countries</label>
                                <select name="geo_countries[]" class="form-select @error('geo_countries') is-invalid @enderror @error('geo_countries.*') is-invalid @enderror" multiple size="10">
                                    @foreach($availableGeoCountries as $code => $label)
                                        <option value="{{ $code }}" {{ in_array($code, $selectedGeoCountries, true) ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                                @error('geo_countries')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('geo_countries.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only used when “Selected European countries only” is chosen.</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Behavior Settings --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Behavior Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_reject_all" value="1" id="show_reject_all"
                                           {{ ($site->settings->show_reject_all ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_reject_all">Show "Reject All" Button</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_close_button" value="1" id="show_close_button"
                                           {{ ($site->settings->show_close_button ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_close_button">Show Close (X) Button</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="reload_on_consent" value="1" id="reload_on_consent"
                                           {{ ($site->settings->reload_on_consent ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="reload_on_consent">Reload Page After Consent</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="respect_dnt" value="1" id="respect_dnt"
                                           {{ ($site->settings->respect_dnt ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="respect_dnt">Respect "Do Not Track" Header</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Consent Expiry (Days)</label>
                                <input type="number" name="consent_expiry_days" class="form-control" min="1" max="730"
                                       value="{{ $site->settings->consent_expiry_days ?? 365 }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Re-consent After (Days)</label>
                                <input type="number" name="reconsent_days" class="form-control" min="1" max="730"
                                       value="{{ $site->settings->reconsent_days ?? 365 }}">
                                <small class="text-muted">GDPR recommends max 12 months</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Script Blocking --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Script Blocking</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_block_scripts" value="1" id="auto_block_scripts"
                                           {{ ($site->settings->auto_block_scripts ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_block_scripts">
                                        <strong>Auto-block Scripts</strong>
                                        <br><small class="text-muted">Automatically block known tracking scripts until consent is given</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="log_consents" value="1" id="log_consents"
                                           {{ ($site->settings->log_consents ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="log_consents">
                                        <strong>Log Consent Records</strong>
                                        <br><small class="text-muted">Store consent records for GDPR compliance proof</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Google Consent Mode --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-google me-2"></i>Google Consent Mode v2</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Google Consent Mode v2 is required for Google Ads and Analytics in the EU since March 2024.
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="gcm_wait_for_update" value="1" id="gcm_wait_for_update"
                                           {{ ($site->settings->gcm_wait_for_update ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gcm_wait_for_update">Wait for consent before sending data</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Wait Timeout (ms)</label>
                                <input type="number" name="gcm_wait_timeout_ms" class="form-control" min="0" max="5000"
                                       value="{{ $site->settings->gcm_wait_timeout_ms ?? 500 }}">
                            </div>
                        </div>
                        <hr>
                        <h6>Default Consent State (before user interaction)</h6>
                        <div class="row g-3 mt-2">
                            @php
                                $gcmDefaults = $site->settings->gcm_default_state ?? [
                                    'ad_storage' => 'denied',
                                    'analytics_storage' => 'denied',
                                    'ad_user_data' => 'denied',
                                    'ad_personalization' => 'denied',
                                    'functionality_storage' => 'denied',
                                    'personalization_storage' => 'denied',
                                    'security_storage' => 'granted',
                                ];
                            @endphp
                            @foreach(['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization', 'functionality_storage', 'personalization_storage', 'security_storage'] as $gcmKey)
                                <div class="col-md-4">
                                    <label class="form-label">{{ str_replace('_', ' ', ucfirst($gcmKey)) }}</label>
                                    <select name="gcm_default_state[{{ $gcmKey }}]" class="form-select">
                                        <option value="denied" {{ ($gcmDefaults[$gcmKey] ?? 'denied') == 'denied' ? 'selected' : '' }}>Denied</option>
                                        <option value="granted" {{ ($gcmDefaults[$gcmKey] ?? '') == 'granted' ? 'selected' : '' }}>Granted</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Custom CSS/JS --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>Custom Code</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Custom CSS</label>
                            <textarea name="custom_css" class="form-control font-monospace" rows="4" placeholder=".tg-gdpr-banner { /* your styles */ }">{{ $site->settings->custom_css ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Custom JavaScript</label>
                            <textarea name="custom_js" class="form-control font-monospace" rows="4" placeholder="// Runs after consent is saved">{{ $site->settings->custom_js ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Save Button --}}
                <div class="card mb-4 position-sticky" style="top: 20px;">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-check-lg me-1"></i> Save All Settings
                        </button>
                        <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary w-100">
                            Cancel
                        </a>
                    </div>
                </div>

                {{-- Preview Info --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0 ps-3">
                            <li class="mb-2">Changes take effect immediately on the site</li>
                            <li class="mb-2">GDPR recommends re-consent every 12 months maximum</li>
                            <li class="mb-2">"Reject All" button is required in some EU countries</li>
                            <li class="mb-2">Google Consent Mode v2 is mandatory for EU Google Ads</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
