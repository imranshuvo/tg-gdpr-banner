<?php

namespace Tests\Feature;

use App\Models\ConsentRecord;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsentRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_consent_accepts_banner_payload_contract(): void
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

        $payload = [
            'site_token' => $site->site_token,
            'consent_id' => (string) Str::uuid(),
            'visitor_hash' => str_repeat('a', 64),
            'consent_categories' => [
                'necessary' => true,
                'functional' => true,
                'analytics' => true,
                'marketing' => false,
            ],
            'consent_method' => 'accept_all',
            'policy_version' => 1,
        ];

        $response = $this->postJson('/api/v1/consents/record', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('consent_records', [
            'site_id' => $site->id,
            'consent_id' => $payload['consent_id'],
            'visitor_hash' => $payload['visitor_hash'],
            'consent_method' => 'accept_all',
            'policy_version' => 1,
        ]);

        $this->assertSame(1, ConsentRecord::count());
    }
}