<?php

namespace Tests\Feature;

use App\Models\ConsentRecord;
use App\Models\Customer;
use App\Models\License;
use App\Models\Site;
use App\Services\Consent\ConsentSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentSigningTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create(['name' => 'Acme', 'email' => 'owner@acme.test']);
        $license  = License::create([
            'customer_id'     => $customer->id,
            'license_key'     => 'TEST-XXXX-XXXX-XXXX',
            'plan'            => 'single',
            'max_activations' => 1,
            'expires_at'      => now()->addYear(),
            'status'          => 'active',
        ]);
        $this->site = Site::create([
            'customer_id' => $customer->id,
            'license_id'  => $license->id,
            'domain'      => 'a.test',
            'site_url'    => 'https://a.test',
            'site_name'   => 'A',
            'site_token'  => 'tok-' . bin2hex(random_bytes(8)),
            'status'      => 'active',
        ]);
    }

    public function test_consent_record_is_signed_on_creation(): void
    {
        $record = $this->makeRecord(['analytics' => true, 'marketing' => false]);

        $this->assertNotEmpty($record->signature);
        $this->assertSame(64, strlen($record->signature), 'HMAC-SHA256 hex is 64 chars');
        $this->assertNotNull($record->signed_at);
    }

    public function test_signature_verifies_on_intact_record(): void
    {
        $record = $this->makeRecord(['analytics' => true]);

        $this->assertTrue($record->fresh()->isSignatureValid());
    }

    public function test_tampering_with_consent_categories_invalidates_signature(): void
    {
        $record = $this->makeRecord(['analytics' => false, 'marketing' => false]);

        // Bypass model events: simulate a direct DB write (e.g. an attacker
        // with DB access flipping a "false" to "true").
        \DB::table('consent_records')->where('id', $record->id)->update([
            'consent_categories' => json_encode(['analytics' => true, 'marketing' => true]),
        ]);

        $this->assertFalse($record->fresh()->isSignatureValid(),
            'Modified consent_categories must fail signature verification');
    }

    public function test_tampering_with_signed_at_invalidates_signature(): void
    {
        $record = $this->makeRecord(['analytics' => true]);

        \DB::table('consent_records')->where('id', $record->id)->update([
            'signed_at' => now()->subYear()->toDateTimeString(),
        ]);

        $this->assertFalse($record->fresh()->isSignatureValid(),
            'Modified signed_at must fail signature verification');
    }

    public function test_record_without_signature_fails_verification(): void
    {
        $record = $this->makeRecord(['analytics' => true]);

        // Strip signature (legacy / pre-migration row).
        \DB::table('consent_records')->where('id', $record->id)->update([
            'signature' => null,
            'signed_at' => null,
        ]);

        $this->assertFalse($record->fresh()->isSignatureValid());
    }

    public function test_canonicalisation_is_key_order_insensitive(): void
    {
        // Same consent payload with keys in different order must produce the
        // same signature — otherwise round-trips through varying serialisers
        // (PHP/JS) would break verification.
        $a = $this->makeRecord(['analytics' => true, 'marketing' => false, 'functional' => true]);
        $b = ConsentRecord::create([
            'site_id'            => $this->site->id,
            'visitor_hash'       => $a->visitor_hash,
            'consent_categories' => ['marketing' => false, 'functional' => true, 'analytics' => true],
            'consent_method'     => $a->consent_method,
            'policy_version'     => $a->policy_version,
        ]);

        // Different consent_id (UUID) means different signatures, but the
        // canonical hash of the consent_categories portion should be equal.
        // Easier check: re-sign $a's record and confirm verification still holds
        // even if categories arrive in another order.
        \DB::table('consent_records')->where('id', $a->id)->update([
            'consent_categories' => json_encode(['marketing' => false, 'functional' => true, 'analytics' => true]),
        ]);

        $this->assertTrue($a->fresh()->isSignatureValid(),
            'Reordering keys in consent_categories must not break verification');
    }

    private function makeRecord(array $categories): ConsentRecord
    {
        return ConsentRecord::create([
            'site_id'            => $this->site->id,
            'visitor_hash'       => str_repeat('a', 64),
            'consent_categories' => $categories,
            'consent_method'     => 'customize',
            'policy_version'     => 1,
        ]);
    }
}
