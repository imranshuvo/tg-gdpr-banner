<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
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
            'stripe_price_id_test' => 'price_TEST_pro',
        ]);
    }

    public function test_checkout_returns_error_when_no_provider_is_configured(): void
    {
        $this->actingAs($this->user)
            ->get(route('customer.checkout', ['plan' => 'pro']))
            ->assertRedirect(route('customer.subscriptions.index'))
            ->assertSessionHas('error');
    }

    public function test_checkout_404s_for_unknown_plan(): void
    {
        $this->actingAs($this->user)
            ->get(route('customer.checkout', ['plan' => 'nonexistent']))
            ->assertNotFound();
    }

    public function test_checkout_404s_for_inactive_plan(): void
    {
        $this->plan->update(['is_active' => false]);

        $this->actingAs($this->user)
            ->get(route('customer.checkout', ['plan' => 'pro']))
            ->assertNotFound();
    }

    public function test_anonymous_users_are_redirected_to_login(): void
    {
        $this->get(route('customer.checkout', ['plan' => 'pro']))
            ->assertRedirect(route('login'));
    }
}
