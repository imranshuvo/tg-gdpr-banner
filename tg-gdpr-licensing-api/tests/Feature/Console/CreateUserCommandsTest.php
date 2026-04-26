<?php

namespace Tests\Feature\Console;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_admin_creates_user_with_admin_role(): void
    {
        $this->artisan('app:create-admin', [
            'email'      => 'owner@cookiely.test',
            '--name'     => 'Owner',
            '--password' => 'secret123',
        ])->assertSuccessful();

        $u = User::where('email', 'owner@cookiely.test')->firstOrFail();
        $this->assertSame('admin', $u->role);
        $this->assertNull($u->customer_id, 'admin must not have a customer linkage');
        $this->assertNotNull($u->email_verified_at);
    }

    public function test_create_admin_promotes_existing_customer(): void
    {
        $existing = User::factory()->create([
            'email' => 'promote@cookiely.test',
            'role'  => 'customer',
        ]);

        $this->artisan('app:create-admin', ['email' => 'promote@cookiely.test'])
            ->expectsConfirmation('User promote@cookiely.test exists with role <customer>. Promote to admin?', 'yes')
            ->assertSuccessful();

        $this->assertSame('admin', $existing->fresh()->role);
    }

    public function test_create_admin_idempotent_for_existing_admin(): void
    {
        User::factory()->create(['email' => 'imran@cookiely.test', 'role' => 'admin']);

        $this->artisan('app:create-admin', ['email' => 'imran@cookiely.test'])
            ->expectsOutputToContain('already a super admin')
            ->assertSuccessful();
    }

    public function test_create_customer_atomically_creates_user_and_customer(): void
    {
        $this->artisan('app:create-customer', [
            'email'      => 'alice@example.test',
            '--name'     => 'Alice',
            '--company'  => 'Acme Ltd',
            '--password' => 'secret123',
        ])->assertSuccessful();

        $u = User::where('email', 'alice@example.test')->firstOrFail();
        $this->assertSame('customer', $u->role);
        $this->assertNotNull($u->customer_id);

        $c = Customer::find($u->customer_id);
        $this->assertSame('Alice', $c->name);
        $this->assertSame('Acme Ltd', $c->company);
        $this->assertSame('alice@example.test', $c->email);
    }

    public function test_create_customer_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->artisan('app:create-customer', [
            'email'      => 'taken@example.test',
            '--password' => 'secret123',
        ])->assertFailed();
    }

    public function test_list_users_filters_by_role(): void
    {
        User::factory()->create(['email' => 'an-admin@example.test', 'role' => 'admin']);
        User::factory()->create(['email' => 'a-customer@example.test', 'role' => 'customer']);

        $this->artisan('app:list-users', ['--role' => 'admin'])
            ->expectsOutputToContain('an-admin@example.test')
            ->doesntExpectOutputToContain('a-customer@example.test')
            ->assertSuccessful();
    }
}
