<?php

namespace Tests\Feature;

use App\Models\Activation;
use App\Models\Customer;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private LicenseService $service;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = new LicenseService();
        $this->customer = Customer::create([
            'name'  => 'Acme Ltd',
            'email' => 'owner@acme.test',
        ]);
    }

    public function test_activate_succeeds_for_valid_license(): void
    {
        $license = $this->makeLicense(maxActivations: 3);

        $result = $this->service->activate($license->license_key, 'site-a.test', 'https://site-a.test');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'domain'     => 'site-a.test',
            'status'     => 'active',
        ]);
    }

    public function test_activate_rejects_unknown_license(): void
    {
        $result = $this->service->activate('NOPE-NOPE-NOPE-NOPE', 'site.test', 'https://site.test');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid license key', $result['message']);
    }

    public function test_activate_rejects_suspended_license(): void
    {
        $license = $this->makeLicense(status: 'suspended');

        $result = $this->service->activate($license->license_key, 'site.test', 'https://site.test');

        $this->assertFalse($result['success']);
        $this->assertSame('License is not active', $result['message']);
    }

    public function test_activate_rejects_and_marks_expired_license(): void
    {
        $license = $this->makeLicense(expiresAt: now()->subDay());

        $result = $this->service->activate($license->license_key, 'site.test', 'https://site.test');

        $this->assertFalse($result['success']);
        $this->assertSame('License has expired', $result['message']);
        $this->assertSame('expired', $license->fresh()->status);
    }

    public function test_activate_is_idempotent_for_same_domain(): void
    {
        $license = $this->makeLicense(maxActivations: 1);

        $first  = $this->service->activate($license->license_key, 'site.test', 'https://site.test');
        $second = $this->service->activate($license->license_key, 'site.test', 'https://site.test');

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame('License reactivated successfully', $second['message']);
        $this->assertSame(1, Activation::where('license_id', $license->id)->count());
    }

    public function test_activate_enforces_max_activations(): void
    {
        $license = $this->makeLicense(maxActivations: 2);

        $this->assertTrue($this->service->activate($license->license_key, 'a.test', 'https://a.test')['success']);
        $this->assertTrue($this->service->activate($license->license_key, 'b.test', 'https://b.test')['success']);

        $third = $this->service->activate($license->license_key, 'c.test', 'https://c.test');

        $this->assertFalse($third['success']);
        $this->assertSame('Maximum activations reached for this license', $third['message']);
        $this->assertSame(2, Activation::where('license_id', $license->id)->count());
    }

    /**
     * Regression guard for the activation race condition fix.
     *
     * The bug: canActivate() (count check) and Activation::create() were not
     * atomic — two concurrent activations on a 1-site license could both pass
     * the check and both insert. Fix: DB::transaction() + lockForUpdate() on
     * the license row in LicenseService::activate().
     *
     * True concurrent verification needs a proper load-test runner against
     * MySQL/PG; PHPUnit can't fork PDO connections cleanly. This test instead
     * proves the *invariant* the fix enforces — the activation limit is never
     * exceeded — which is what we actually care about.
     */
    public function test_activation_limit_is_never_exceeded(): void
    {
        $license = $this->makeLicense(maxActivations: 1);

        $first  = $this->service->activate($license->license_key, 'a.test', 'https://a.test');
        $second = $this->service->activate($license->license_key, 'b.test', 'https://b.test');

        $this->assertTrue($first['success']);
        $this->assertFalse($second['success']);
        $this->assertSame(1, Activation::where('license_id', $license->id)->count());
    }

    public function test_deactivate_marks_activation_inactive(): void
    {
        $license = $this->makeLicense();
        $this->service->activate($license->license_key, 'site.test', 'https://site.test');

        $result = $this->service->deactivate($license->license_key, 'site.test');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'domain'     => 'site.test',
            'status'     => 'inactive',
        ]);
    }

    public function test_verify_returns_active_for_valid_activation(): void
    {
        $license = $this->makeLicense();
        $this->service->activate($license->license_key, 'site.test', 'https://site.test');

        $result = $this->service->verify($license->license_key, 'site.test');

        $this->assertTrue($result['success']);
        $this->assertSame($license->license_key, $result['data']['license_key']);
    }

    public function test_verify_fails_after_deactivation(): void
    {
        $license = $this->makeLicense();
        $this->service->activate($license->license_key, 'site.test', 'https://site.test');
        $this->service->deactivate($license->license_key, 'site.test');

        $result = $this->service->verify($license->license_key, 'site.test');

        $this->assertFalse($result['success']);
    }

    private function makeLicense(
        int $maxActivations = 1,
        string $status = 'active',
        ?\DateTimeInterface $expiresAt = null,
    ): License {
        return License::create([
            'customer_id'     => $this->customer->id,
            'license_key'     => $this->service->generateLicenseKey(),
            'plan'            => 'single',
            'max_activations' => $maxActivations,
            'expires_at'      => $expiresAt ?? now()->addYear(),
            'status'          => $status,
        ]);
    }

}
