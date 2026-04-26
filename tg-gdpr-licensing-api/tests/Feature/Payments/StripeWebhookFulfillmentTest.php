<?php

namespace Tests\Feature\Payments;

use App\Models\Customer;
use App\Models\License;
use App\Models\Plan;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: a successful Stripe checkout MUST result in a usable License.
 *
 * Pre-fix, the webhook only updated the Subscription row — the customer paid
 * and got nothing they could activate. These tests pin the post-payment
 * fulfillment so it stays wired.
 */
class StripeWebhookFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create(['name' => 'Acme', 'email' => 'a@a.test']);
        $this->user = User::factory()->create([
            'role'        => 'customer',
            'customer_id' => $customer->id,
        ]);
        $this->plan = Plan::create([
            'slug'      => 'pro',
            'name'      => 'Professional',
            'max_sites' => 5,
            'features'  => ['Up to 5 sites'],
            'is_active' => true,
        ]);
    }

    public function test_provision_creates_a_usable_license(): void
    {
        $license = app(LicenseService::class)
            ->provisionForCheckout($this->user, $this->plan, 'sub_TEST_123');

        $this->assertNotEmpty($license->license_key);
        $this->assertSame('active', $license->status);
        $this->assertSame(5, $license->max_activations);
        $this->assertSame($this->plan->id, $license->plan_id);
        $this->assertSame('sub_TEST_123', $license->provider_subscription_id);
        $this->assertSame($this->user->customer_id, $license->customer_id);
        $this->assertTrue($license->expires_at->greaterThan(now()->addMonths(11)));
    }

    public function test_provision_is_idempotent_on_webhook_redelivery(): void
    {
        $service = app(LicenseService::class);

        $first  = $service->provisionForCheckout($this->user, $this->plan, 'sub_TEST_456');
        $second = $service->provisionForCheckout($this->user, $this->plan, 'sub_TEST_456');

        $this->assertSame($first->id, $second->id,
            'Webhook re-delivery must return the same License, not create a duplicate.');
        $this->assertSame(1, License::where('provider_subscription_id', 'sub_TEST_456')->count());
    }

    public function test_provisioned_license_can_be_activated_by_plugin(): void
    {
        $license = app(LicenseService::class)
            ->provisionForCheckout($this->user, $this->plan, 'sub_TEST_789');

        $result = app(LicenseService::class)
            ->activate($license->license_key, 'site-a.test', 'https://site-a.test');

        $this->assertTrue($result['success'], 'Newly provisioned license must activate successfully.');
        $this->assertSame($license->license_key, $result['data']['license_key']);
    }

    public function test_provision_fails_for_user_without_customer(): void
    {
        $orphan = User::factory()->create(['role' => 'customer', 'customer_id' => null]);

        $this->expectException(\RuntimeException::class);

        app(LicenseService::class)->provisionForCheckout($orphan, $this->plan, 'sub_TEST_orphan');
    }
}
