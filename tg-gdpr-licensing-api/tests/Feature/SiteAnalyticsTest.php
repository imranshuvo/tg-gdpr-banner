<?php

namespace Tests\Feature;

use App\Models\ConsentRecord;
use App\Models\Customer;
use App\Models\Site;
use App\Models\SiteSession;
use App\Models\SiteUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private int $initialOutputBufferLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initialOutputBufferLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->initialOutputBufferLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    public function test_site_analytics_page_renders_aggregated_metrics_and_recent_consents(): void
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

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        SiteSession::create([
            'site_id' => $site->id,
            'date' => now()->subDays(5)->toDateString(),
            'total_sessions' => 20,
            'banner_shown' => 18,
            'consent_given' => 8,
            'consent_denied' => 4,
            'consent_customized' => 3,
            'no_action' => 3,
            'accepted_functional' => 10,
            'accepted_analytics' => 9,
            'accepted_marketing' => 5,
            'geo_breakdown' => ['US' => 12, 'DE' => 6],
            'device_breakdown' => ['desktop' => 11, 'mobile' => 9],
        ]);

        SiteSession::create([
            'site_id' => $site->id,
            'date' => now()->subDays(2)->toDateString(),
            'total_sessions' => 10,
            'banner_shown' => 9,
            'consent_given' => 2,
            'consent_denied' => 1,
            'consent_customized' => 1,
            'no_action' => 5,
            'accepted_functional' => 2,
            'accepted_analytics' => 1,
            'accepted_marketing' => 0,
            'geo_breakdown' => ['US' => 4, 'GB' => 5],
            'device_breakdown' => ['desktop' => 4, 'mobile' => 5, 'tablet' => 1],
        ]);

        SiteUsage::create([
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'year' => now()->year,
            'month' => now()->month,
            'total_sessions' => 450,
            'total_consents' => 250,
            'session_limit' => 1000,
        ]);

        ConsentRecord::create([
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('a', 64),
            'country_code' => 'US',
            'device_type' => 'desktop',
            'consent_categories' => [
                'necessary' => true,
                'functional' => true,
                'analytics' => true,
                'marketing' => false,
            ],
            'consent_method' => 'accept_all',
            'gcm_state' => [
                'ad_storage' => 'granted',
                'analytics_storage' => 'granted',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
            ],
            'policy_version' => 2,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        ConsentRecord::create([
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('b', 64),
            'country_code' => 'DE',
            'device_type' => 'mobile',
            'consent_categories' => [
                'necessary' => true,
                'functional' => false,
                'analytics' => false,
                'marketing' => false,
            ],
            'consent_method' => 'reject_all',
            'gcm_state' => [
                'ad_storage' => 'denied',
                'analytics_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
            ],
            'policy_version' => 2,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        ConsentRecord::create([
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('c', 64),
            'country_code' => 'GB',
            'device_type' => 'tablet',
            'consent_categories' => [
                'necessary' => true,
                'functional' => true,
                'analytics' => true,
                'marketing' => false,
            ],
            'consent_method' => 'customize',
            'gcm_state' => [
                'ad_storage' => 'denied',
                'analytics_storage' => 'granted',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
            ],
            'policy_version' => 2,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sites.analytics', $site));

        $response->assertOk();
        $response->assertViewHas('analytics', function (array $analytics) {
            return $analytics['total_sessions'] === 30
                && $analytics['total_consents'] === 19
                && $analytics['accept_all_count'] === 10
                && abs($analytics['consent_rate'] - 70.4) < 0.1
                && abs($analytics['accept_all_rate'] - 52.6) < 0.1
                && abs($analytics['usage_percentage'] - 45.0) < 0.1
                && ($analytics['category_rates']['analytics'] ?? null) === 52.6
                && ($analytics['gcm_stats']['analytics_storage']['granted'] ?? null) === 2
                && ($analytics['top_countries']['US'] ?? null) === 16
                && ($analytics['device_breakdown']['desktop'] ?? null) === 15;
        });
        $response->assertViewHas('recentConsents', fn ($recentConsents) => $recentConsents->count() === 3);
        $response->assertSee('Accept All');
        $response->assertSee('Reject All');
        $response->assertSee('Customize');
        $response->assertSee('Top Countries');
        $response->assertSee('Device Mix');
        $response->assertDontSee('cdn.jsdelivr.net/npm/apexcharts');
    }

    public function test_site_analytics_keeps_consent_rate_unavailable_without_banner_impressions(): void
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

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        SiteSession::create([
            'site_id' => $site->id,
            'date' => now()->subDay()->toDateString(),
            'total_sessions' => 12,
            'banner_shown' => 0,
            'consent_given' => 0,
            'consent_denied' => 0,
            'consent_customized' => 0,
            'no_action' => 0,
            'accepted_functional' => 0,
            'accepted_analytics' => 0,
            'accepted_marketing' => 0,
            'geo_breakdown' => [],
            'device_breakdown' => ['desktop' => 12],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sites.analytics', $site));

        $response->assertOk();
        $response->assertViewHas('analytics', fn (array $analytics) => $analytics['consent_rate'] === null);
        $response->assertSee('Awaiting banner impressions');
    }
}