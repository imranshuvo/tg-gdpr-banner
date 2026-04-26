<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: customer dashboard must render without a 500.
 *
 * Pre-fix, DashboardController referenced `$this->activityLogger` without a
 * constructor or property — every authed customer hitting / would 500 the moment
 * they reached the dashboard.
 */
class DashboardRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_renders_for_authed_customer(): void
    {
        $customer = Customer::create(['name' => 'Acme', 'email' => 'a@a.test']);
        $user = User::factory()->create([
            'role'        => 'customer',
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertOk();
    }

    public function test_customer_with_no_customer_record_is_403d(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => null]);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }
}
