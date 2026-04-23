<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Site;
use App\Models\SiteSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_create_flow_persists_selected_geo_scope(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Ltd',
            'email' => 'owner@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sites.store'), [
            'customer_id' => $customer->id,
            'license_id' => null,
            'domain' => 'example.com',
            'site_url' => 'https://example.com',
            'site_name' => 'Example',
            'status' => 'trial',
            'tcf_enabled' => '1',
            'gcm_enabled' => '1',
            'geo_targeting_mode' => 'selected',
            'geo_countries' => ['DE', 'FR'],
        ]);

        $site = Site::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.sites.show', $site));
        $this->assertTrue($site->geo_targeting_enabled);
        $this->assertSame(['DE', 'FR'], $site->geo_countries);
    }

    public function test_site_edit_flow_can_switch_geo_scope_to_all_countries(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Ltd',
            'email' => 'owner@example.com',
        ]);

        $site = Site::create([
            'customer_id' => $customer->id,
            'domain' => 'example.com',
            'site_url' => 'https://example.com',
            'site_name' => 'Example',
            'status' => 'active',
            'geo_targeting_enabled' => true,
            'geo_countries' => ['EU'],
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sites.update', $site), [
            'customer_id' => $customer->id,
            'license_id' => null,
            'domain' => 'example.com',
            'site_url' => 'https://example.com',
            'site_name' => 'Example',
            'status' => 'active',
            'tcf_enabled' => '1',
            'gcm_enabled' => '1',
            'geo_targeting_mode' => 'all',
        ]);

        $response->assertRedirect(route('admin.sites.show', $site));

        $site->refresh();

        $this->assertFalse($site->geo_targeting_enabled);
        $this->assertSame([], $site->geo_countries ?? []);
    }

    public function test_site_settings_update_persists_selected_geo_scope_for_plugin_payload(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Ltd',
            'email' => 'owner@example.com',
        ]);

        $site = Site::create([
            'customer_id' => $customer->id,
            'domain' => 'example.com',
            'site_url' => 'https://example.com',
            'site_name' => 'Example',
            'status' => 'active',
        ]);

        SiteSettings::create(['site_id' => $site->id]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sites.settings.update', $site), $this->settingsPayload([
            'geo_targeting_mode' => 'selected',
            'geo_countries' => ['DE', 'FR'],
            'heading' => 'Configured for selected EU countries',
        ]));

        $response->assertRedirect(route('admin.sites.settings', $site));

        $site->refresh();

        $this->assertTrue($site->geo_targeting_enabled);
        $this->assertSame(['DE', 'FR'], $site->geo_countries);

        $pluginSettings = $site->fresh(['settings'])->getSettingsForPlugin();

        $this->assertSame('selected', $pluginSettings['geo_targeting_mode']);
        $this->assertSame(['DE', 'FR'], $pluginSettings['geo_countries']);
    }

    public function test_site_settings_update_can_disable_geo_targeting_for_all_countries(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Ltd',
            'email' => 'owner@example.com',
        ]);

        $site = Site::create([
            'customer_id' => $customer->id,
            'domain' => 'example.com',
            'site_url' => 'https://example.com',
            'site_name' => 'Example',
            'status' => 'active',
            'geo_targeting_enabled' => true,
            'geo_countries' => ['EU'],
        ]);

        SiteSettings::create(['site_id' => $site->id]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sites.settings.update', $site), $this->settingsPayload([
            'geo_targeting_mode' => 'all',
        ]));

        $response->assertRedirect(route('admin.sites.settings', $site));

        $site->refresh();

        $this->assertFalse($site->geo_targeting_enabled);
        $this->assertSame([], $site->geo_countries ?? []);

        $pluginSettings = $site->fresh(['settings'])->getSettingsForPlugin();

        $this->assertSame('all', $pluginSettings['geo_targeting_mode']);
        $this->assertSame([], $pluginSettings['geo_countries']);
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'geo_targeting_mode' => 'eu',
            'banner_position' => 'bottom',
            'banner_layout' => 'bar',
            'primary_color' => '#1e40af',
            'accent_color' => '#3b82f6',
            'text_color' => '#1f2937',
            'bg_color' => '#ffffff',
            'button_style' => 'rounded',
            'heading' => 'We value your privacy',
            'message' => 'We use cookies to enhance your browsing experience.',
            'accept_all_text' => 'Accept All',
            'reject_all_text' => 'Reject All',
            'customize_text' => 'Customize',
            'save_preferences_text' => 'Save Preferences',
            'privacy_policy_url' => 'https://example.com/privacy',
            'privacy_policy_text' => 'Privacy Policy',
            'category_labels' => [
                'necessary' => 'Essential',
                'functional' => 'Functional',
                'analytics' => 'Analytics',
                'marketing' => 'Marketing',
            ],
            'category_descriptions' => [
                'necessary' => 'Required cookies.',
                'functional' => 'Functional cookies.',
                'analytics' => 'Analytics cookies.',
                'marketing' => 'Marketing cookies.',
            ],
            'show_reject_all' => '1',
            'show_close_button' => '0',
            'reload_on_consent' => '0',
            'respect_dnt' => '1',
            'consent_expiry_days' => 365,
            'reconsent_days' => 365,
            'auto_block_scripts' => '1',
            'log_consents' => '1',
            'gcm_wait_for_update' => '1',
            'gcm_wait_timeout_ms' => 500,
            'gcm_default_state' => [
                'ad_storage' => 'denied',
                'analytics_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
                'functionality_storage' => 'denied',
                'personalization_storage' => 'denied',
                'security_storage' => 'granted',
            ],
        ], $overrides);
    }
}