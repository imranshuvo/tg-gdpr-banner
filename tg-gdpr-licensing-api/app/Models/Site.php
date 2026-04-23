<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    public const EUROPEAN_COUNTRY_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI',
        'NO', 'CH',
    ];

    protected $fillable = [
        'customer_id',
        'license_id',
        'domain',
        'site_url',
        'site_name',
        'site_token',
        'tcf_enabled',
        'gcm_enabled',
        'geo_targeting_enabled',
        'geo_countries',
        'policy_version',
        'policy_updated_at',
        'last_scan_at',
        'cookies_detected',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'tcf_enabled' => 'boolean',
        'gcm_enabled' => 'boolean',
        'geo_targeting_enabled' => 'boolean',
        'geo_countries' => 'array',
        'policy_updated_at' => 'datetime',
        'last_scan_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($site) {
            if (empty($site->site_token)) {
                $site->site_token = Str::random(64);
            }
            if (empty($site->trial_ends_at)) {
                $site->trial_ends_at = now()->addDays(30);
            }
        });
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SiteSettings::class);
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function cookies(): HasMany
    {
        return $this->hasMany(SiteCookie::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SiteSession::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(SiteUsage::class);
    }

    public function dsarRequests(): HasMany
    {
        return $this->hasMany(DsarRequest::class);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isTrialExpired(): bool
    {
        return $this->isTrial() && $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function incrementPolicyVersion(): void
    {
        $this->increment('policy_version');
        $this->update(['policy_updated_at' => now()]);
    }

    public function getGeoTargetingMode(): string
    {
        if (!$this->geo_targeting_enabled) {
            return 'all';
        }

        $configuredCountries = array_values(array_filter($this->geo_countries ?? []));

        if (empty($configuredCountries) || in_array('EU', $configuredCountries, true)) {
            return 'eu';
        }

        return 'selected';
    }

    public function getGeoTargetCountries(): array
    {
        $mode = $this->getGeoTargetingMode();

        if ($mode === 'all') {
            return [];
        }

        if ($mode === 'eu') {
            return self::EUROPEAN_COUNTRY_CODES;
        }

        return array_values(array_unique(array_filter(
            array_map('strtoupper', $this->geo_countries ?? []),
            fn (string $countryCode) => in_array($countryCode, self::EUROPEAN_COUNTRY_CODES, true)
        )));
    }

    public function getSettingsForPlugin(): array
    {
        $settings = $this->settings;
        
        return [
            'site_token' => $this->site_token,
            'policy_version' => $this->policy_version,
            'tcf_enabled' => $this->tcf_enabled,
            'gcm_enabled' => $this->gcm_enabled,
            'geo_targeting_enabled' => $this->geo_targeting_enabled,
            'geo_targeting_mode' => $this->getGeoTargetingMode(),
            'geo_countries' => $this->geo_targeting_enabled
                ? ($this->getGeoTargetingMode() === 'eu' ? ['EU'] : $this->getGeoTargetCountries())
                : [],
            'banner' => [
                'position' => $settings->banner_position ?? 'bottom',
                'layout' => $settings->banner_layout ?? 'bar',
                'primary_color' => $settings->primary_color ?? '#1e40af',
                'accent_color' => $settings->accent_color ?? '#3b82f6',
                'text_color' => $settings->text_color ?? '#1f2937',
                'bg_color' => $settings->bg_color ?? '#ffffff',
                'button_style' => $settings->button_style ?? 'rounded',
            ],
            'content' => [
                'heading' => $settings->heading ?? 'We value your privacy',
                'message' => $settings->message ?? 'We use cookies to enhance your browsing experience.',
                'accept_all_text' => $settings->accept_all_text ?? 'Accept All',
                'reject_all_text' => $settings->reject_all_text ?? 'Reject All',
                'customize_text' => $settings->customize_text ?? 'Customize',
                'save_preferences_text' => $settings->save_preferences_text ?? 'Save Preferences',
                'privacy_policy_url' => $settings->privacy_policy_url,
                'privacy_policy_text' => $settings->privacy_policy_text ?? 'Privacy Policy',
            ],
            'categories' => $settings->category_labels ?? [
                'necessary' => 'Essential',
                'functional' => 'Functional',
                'analytics' => 'Analytics',
                'marketing' => 'Marketing',
            ],
            'category_descriptions' => $settings->category_descriptions ?? [],
            'behavior' => [
                'show_reject_all' => $settings->show_reject_all ?? true,
                'show_close_button' => $settings->show_close_button ?? false,
                'close_on_scroll' => $settings->close_on_scroll ?? false,
                'close_on_timeout' => $settings->close_on_timeout ?? false,
                'timeout_seconds' => $settings->timeout_seconds ?? 0,
                'reload_on_consent' => $settings->reload_on_consent ?? false,
                'consent_expiry_days' => $settings->consent_expiry_days ?? 365,
                'reconsent_days' => $settings->reconsent_days ?? 365,
            ],
            'blocking' => [
                'auto_block_scripts' => $settings->auto_block_scripts ?? true,
                'custom_script_patterns' => $settings->custom_script_patterns ?? [],
                'script_whitelist' => $settings->script_whitelist ?? [],
            ],
            'advanced' => [
                'respect_dnt' => $settings->respect_dnt ?? false,
                'log_consents' => $settings->log_consents ?? true,
                'custom_css' => $settings->custom_css,
                'custom_js' => $settings->custom_js,
            ],
            'gcm' => [
                'default_state' => $settings->gcm_default_state ?? [
                    'ad_storage' => 'denied',
                    'analytics_storage' => 'denied',
                    'ad_user_data' => 'denied',
                    'ad_personalization' => 'denied',
                    'functionality_storage' => 'denied',
                    'personalization_storage' => 'denied',
                    'security_storage' => 'granted',
                ],
                'wait_for_update' => $settings->gcm_wait_for_update ?? true,
                'wait_timeout_ms' => $settings->gcm_wait_timeout_ms ?? 500,
                'region_settings' => $settings->gcm_region_settings ?? [],
            ],
            'tcf' => [
                'purposes' => $settings->tcf_purposes ?? [],
                'vendors' => $settings->tcf_vendors ?? [],
                'legitimate_interests' => $settings->tcf_legitimate_interests ?? [],
            ],
        ];
    }

    // Session/Usage helpers
    public function getCurrentMonthSessions(): int
    {
        return $this->sessions()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('total_sessions');
    }

    public function getSessionLimit(): int
    {
        // Based on license plan
        $plan = $this->license?->plan ?? 'trial';
        
        return match ($plan) {
            'starter' => 25000,
            'growth' => 100000,
            'business' => 500000,
            'enterprise' => PHP_INT_MAX,
            default => 10000, // trial limit
        };
    }
}
