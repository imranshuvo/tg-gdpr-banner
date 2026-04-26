<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: registration MUST also create a Customer and link the User to it.
 *
 * Pre-fix, registration only created a User row with `customer_id = null`.
 * Stripe checkout would then succeed but `onCheckoutCompleted` bailed on the
 * null customer_id, so the customer paid and never received a License.
 */
class RegistrationCreatesCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_linked_customer_record(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Acme Corp',
            'email'                 => 'owner@acme.test',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'owner@acme.test')->firstOrFail();
        $this->assertNotNull($user->customer_id, 'Registered user must be linked to a Customer.');

        $customer = Customer::find($user->customer_id);
        $this->assertNotNull($customer);
        $this->assertSame('Acme Corp', $customer->name);
        $this->assertSame('owner@acme.test', $customer->email);
    }

    public function test_registration_is_atomic_user_and_customer_or_neither(): void
    {
        // Pre-existing User with the same email — Customer creation should still
        // succeed (Customer doesn't enforce email uniqueness), but the User
        // creation will fail validation. Both should not exist after.
        User::factory()->create(['email' => 'taken@acme.test']);

        $this->post('/register', [
            'name'                  => 'Whoever',
            'email'                 => 'taken@acme.test',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        // No orphan Customer should have been created.
        $this->assertSame(0, Customer::where('email', 'taken@acme.test')->count(),
            'Failed registration must not leave an orphan Customer behind.');
    }
}
