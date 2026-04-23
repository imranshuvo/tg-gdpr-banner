<?php

namespace Tests\Feature;

use App\Mail\DsarVerificationMail;
use App\Models\ConsentRecord;
use App\Models\Customer;
use App\Models\DsarRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DsarRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dsar_submission_accepts_normalized_payload_and_sends_verification_email(): void
    {
        Mail::fake();

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

        $response = $this->postJson('/api/v1/dsar/submit', [
            'site_token' => $site->site_token,
            'type' => 'access',
            'email' => 'subject@example.com',
            'first_name' => 'Data',
            'last_name' => 'Subject',
            'message' => 'Please send me my data export.',
            'visitor_hash' => str_repeat('a', 64),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('dsar_requests', [
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'request_type' => 'access',
            'requester_email' => 'subject@example.com',
            'requester_name' => 'Data Subject',
            'visitor_hash' => str_repeat('a', 64),
            'status' => 'pending_verification',
        ]);

        Mail::assertSent(DsarVerificationMail::class);
    }

    public function test_erasure_processing_only_affects_matching_visitor_hash(): void
    {
        Mail::fake();

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

        ConsentRecord::create([
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('a', 64),
            'consent_categories' => ['analytics' => true],
            'consent_method' => 'accept_all',
            'policy_version' => 1,
        ]);

        ConsentRecord::create([
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('b', 64),
            'consent_categories' => ['analytics' => false],
            'consent_method' => 'reject_all',
            'policy_version' => 1,
        ]);

        $dsarRequest = DsarRequest::create([
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'request_type' => 'erasure',
            'requester_email' => 'subject@example.com',
            'requester_name' => 'Data Subject',
            'visitor_hash' => str_repeat('a', 64),
            'status' => 'processing',
            'verified_at' => now(),
            'verified_method' => 'email',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.dsar.process', $dsarRequest), [
            'action' => 'complete',
            'admin_notes' => 'Processed and removed matching consent records.',
        ]);

        $response->assertRedirect(route('admin.dsar.index'));

        $this->assertDatabaseMissing('consent_records', [
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('a', 64),
        ]);

        $this->assertDatabaseHas('consent_records', [
            'site_id' => $site->id,
            'visitor_hash' => str_repeat('b', 64),
        ]);

        $this->assertDatabaseHas('dsar_requests', [
            'id' => $dsarRequest->id,
            'status' => 'completed',
        ]);
    }

    public function test_dsar_submission_rejects_invalid_visitor_hash_format(): void
    {
        Mail::fake();

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

        $response = $this->postJson('/api/v1/dsar/submit', [
            'site_token' => $site->site_token,
            'request_type' => 'access',
            'requester_email' => 'subject@example.com',
            'visitor_hash' => 'short-hash',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('dsar_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_scoped_dsar_completion_requires_notes_when_no_records_match(): void
    {
        Mail::fake();

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

        $dsarRequest = DsarRequest::create([
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'request_type' => 'erasure',
            'requester_email' => 'subject@example.com',
            'requester_name' => 'Data Subject',
            'visitor_hash' => str_repeat('c', 64),
            'status' => 'processing',
            'verified_at' => now(),
            'verified_method' => 'email',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.dsar.show', $dsarRequest))->post(route('admin.dsar.process', $dsarRequest), [
            'action' => 'complete',
            'admin_notes' => '',
        ]);

        $response->assertRedirect(route('admin.dsar.show', $dsarRequest));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('dsar_requests', [
            'id' => $dsarRequest->id,
            'status' => 'processing',
        ]);
    }
}