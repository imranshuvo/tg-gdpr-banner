<?php

namespace Tests\Feature;

use App\Models\ConsentRecord;
use App\Models\Customer;
use App\Models\License;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeExpiredConsentsTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create(['name' => 'Acme', 'email' => 'o@a.test']);
        $license  = License::create([
            'customer_id' => $customer->id, 'license_key' => 'TEST-XXXX-XXXX-XXXX',
            'plan' => 'single', 'max_activations' => 1,
            'expires_at' => now()->addYear(), 'status' => 'active',
        ]);
        $this->site = Site::create([
            'customer_id' => $customer->id, 'license_id' => $license->id,
            'domain' => 'a.test', 'site_url' => 'https://a.test', 'site_name' => 'A',
            'site_token' => 'tok-' . bin2hex(random_bytes(8)), 'status' => 'active',
        ]);
    }

    public function test_command_deletes_expired_consent_records(): void
    {
        $expired = $this->makeRecord(['analytics' => true], expiresAt: now()->subDay());
        $live    = $this->makeRecord(['analytics' => true], expiresAt: now()->addYear());
        $undated = $this->makeRecord(['analytics' => true], expiresAt: null);

        $this->artisan('consents:purge-expired')
            ->expectsOutputToContain('Deleted 1 expired consent record')
            ->assertSuccessful();

        $this->assertDatabaseMissing('consent_records', ['id' => $expired->id]);
        $this->assertDatabaseHas('consent_records',    ['id' => $live->id]);
        // NULL expires_at must NOT be deleted — those rows pre-date the column.
        $this->assertDatabaseHas('consent_records',    ['id' => $undated->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        $this->makeRecord(['analytics' => true], expiresAt: now()->subDay());

        $this->artisan('consents:purge-expired', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN: would delete 1')
            ->assertSuccessful();

        $this->assertSame(1, ConsentRecord::count());
    }

    public function test_no_op_when_nothing_expired(): void
    {
        $this->makeRecord(['analytics' => true], expiresAt: now()->addYear());

        $this->artisan('consents:purge-expired')
            ->expectsOutputToContain('No expired consent records.')
            ->assertSuccessful();
    }

    private function makeRecord(array $categories, ?\DateTimeInterface $expiresAt): ConsentRecord
    {
        return ConsentRecord::create([
            'site_id'            => $this->site->id,
            'visitor_hash'       => bin2hex(random_bytes(32)),
            'consent_categories' => $categories,
            'consent_method'     => 'customize',
            'policy_version'     => 1,
            'expires_at'         => $expiresAt,
        ]);
    }
}
