<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Site;
use App\Models\SiteSession;
use App\Models\SiteUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_sync_accepts_plugin_daily_aggregate_contract_and_preserves_newer_live_counts(): void
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

        SiteSession::create([
            'site_id' => $site->id,
            'date' => now()->toDateString(),
            'total_sessions' => 0,
            'banner_shown' => 0,
            'consent_given' => 6,
            'consent_denied' => 1,
            'consent_customized' => 0,
            'no_action' => 0,
            'accepted_functional' => 6,
            'accepted_analytics' => 6,
            'accepted_marketing' => 6,
            'geo_breakdown' => [],
            'device_breakdown' => ['desktop' => 3],
        ]);

        $response = $this->postJson('/api/v1/sessions/sync', [
            'site_token' => $site->site_token,
            'sessions' => [[
                'date' => now()->toDateString(),
                'total_sessions' => 25,
                'banner_shown' => 12,
                'consent_given' => 4,
                'consent_denied' => 1,
                'consent_customized' => 1,
                'no_action' => 6,
                'accepted_functional' => 5,
                'accepted_analytics' => 4,
                'accepted_marketing' => 2,
                'geo_breakdown' => ['DE' => 7],
                'device_breakdown' => ['desktop' => 10, 'mobile' => 15],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('usage.current_sessions', 25);

        $session = SiteSession::where('site_id', $site->id)->whereDate('date', now()->toDateString())->firstOrFail();

        $this->assertSame(25, $session->total_sessions);
        $this->assertSame(12, $session->banner_shown);
        $this->assertSame(6, $session->consent_given);
        $this->assertSame(1, $session->consent_denied);
        $this->assertSame(1, $session->consent_customized);
        $this->assertSame(6, $session->accepted_functional);
        $this->assertSame(6, $session->accepted_analytics);
        $this->assertSame(6, $session->accepted_marketing);
        $this->assertSame(['DE' => 7], $session->geo_breakdown);
        $this->assertSame(['mobile' => 15, 'desktop' => 10], $session->device_breakdown);
        $this->assertSame(25, SiteUsage::where('site_id', $site->id)->firstOrFail()->total_sessions);
        $this->assertSame(8, SiteUsage::where('site_id', $site->id)->firstOrFail()->total_consents);
    }
}