<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSettings extends Model
{
    protected $table = 'site_settings';
    
    protected $fillable = [
        'site_id',
        // Banner Appearance
        'banner_position',
        'banner_layout',
        'primary_color',
        'accent_color',
        'text_color',
        'bg_color',
        'button_style',
        // Banner Content
        'heading',
        'message',
        'accept_all_text',
        'reject_all_text',
        'customize_text',
        'save_preferences_text',
        'privacy_policy_url',
        'privacy_policy_text',
        // Category Labels
        'category_labels',
        'category_descriptions',
        // Behavior
        'show_reject_all',
        'show_close_button',
        'close_on_scroll',
        'close_on_timeout',
        'timeout_seconds',
        'reload_on_consent',
        'consent_expiry_days',
        'reconsent_days',
        // Script Blocking
        'auto_block_scripts',
        'custom_script_patterns',
        'script_whitelist',
        // Advanced
        'respect_dnt',
        'log_consents',
        'custom_css',
        'custom_js',
        // TCF
        'tcf_purposes',
        'tcf_vendors',
        'tcf_legitimate_interests',
        // GCM
        'gcm_default_state',
        'gcm_wait_for_update',
        'gcm_wait_timeout_ms',
        'gcm_region_settings',
    ];

    protected $casts = [
        'show_reject_all' => 'boolean',
        'show_close_button' => 'boolean',
        'close_on_scroll' => 'boolean',
        'close_on_timeout' => 'boolean',
        'reload_on_consent' => 'boolean',
        'auto_block_scripts' => 'boolean',
        'respect_dnt' => 'boolean',
        'log_consents' => 'boolean',
        'gcm_wait_for_update' => 'boolean',
        'category_labels' => 'array',
        'category_descriptions' => 'array',
        'custom_script_patterns' => 'array',
        'script_whitelist' => 'array',
        'tcf_purposes' => 'array',
        'tcf_vendors' => 'array',
        'tcf_legitimate_interests' => 'array',
        'gcm_default_state' => 'array',
        'gcm_region_settings' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Get default GCM state
    public function getDefaultGcmState(): array
    {
        return $this->gcm_default_state ?? [
            'ad_storage' => 'denied',
            'analytics_storage' => 'denied',
            'ad_user_data' => 'denied',
            'ad_personalization' => 'denied',
            'functionality_storage' => 'denied',
            'personalization_storage' => 'denied',
            'security_storage' => 'granted',
        ];
    }

    // Get category labels with defaults
    public function getCategoryLabels(): array
    {
        return array_merge([
            'necessary' => 'Essential',
            'functional' => 'Functional',
            'analytics' => 'Analytics',
            'marketing' => 'Marketing',
        ], $this->category_labels ?? []);
    }

    // Get category descriptions with defaults
    public function getCategoryDescriptions(): array
    {
        return array_merge([
            'necessary' => 'These cookies are essential for the website to function properly.',
            'functional' => 'These cookies enable personalized features and functionality.',
            'analytics' => 'These cookies help us understand how visitors interact with the website.',
            'marketing' => 'These cookies are used to deliver personalized advertisements.',
        ], $this->category_descriptions ?? []);
    }
}
