<?php

namespace Tests\Feature\Customer;

use App\Models\Activation;
use App\Models\Customer;
use App\Models\License;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create(['name' => 'Acme', 'email' => 'owner@acme.test']);
        $this->user = User::factory()->create([
            'role'        => 'customer',
            'customer_id' => $this->customer->id,
        ]);

        $license = License::create([
            'customer_id'     => $this->customer->id,
            'license_key'     => 'TEST-XXXX-XXXX-XXXX',
            'plan'            => 'pro',
            'max_activations' => 5,
            'expires_at'      => now()->addYear(),
            'status'          => 'active',
        ]);

        $this->site = Site::create([
            'customer_id' => $this->customer->id,
            'license_id'  => $license->id,
            'domain'      => 'a.test',
            'site_url'    => 'https://a.test',
            'site_name'   => 'A',
            'site_token'  => 'tok-' . bin2hex(random_bytes(8)),
            'status'      => 'active',
        ]);
    }

    public function test_customer_can_list_their_own_sites(): void
    {
        $this->actingAs($this->user)
            ->get(route('customer.sites.index'))
            ->assertOk()
            ->assertSee($this->site->domain);
    }

    public function test_customer_cannot_see_other_customers_sites(): void
    {
        $other = Customer::create(['name' => 'Other', 'email' => 'o@o.test']);
        $otherSite = Site::create([
            'customer_id' => $other->id,
            'license_id'  => null,
            'domain'      => 'forbidden.test',
            'site_url'    => 'https://forbidden.test',
            'site_name'   => 'Forbidden',
            'site_token'  => 'tok-' . bin2hex(random_bytes(8)),
            'status'      => 'active',
        ]);

        $this->actingAs($this->user)
            ->get(route('customer.sites.index'))
            ->assertOk()
            ->assertDontSee('forbidden.test');

        $this->actingAs($this->user)
            ->get(route('customer.sites.show', $otherSite))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('customer.sites.analytics', $otherSite))
            ->assertForbidden();
    }

    public function test_customer_can_open_their_site_detail_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('customer.sites.show', $this->site))
            ->assertOk()
            ->assertSee('Activated domains')
            ->assertSee($this->site->domain);
    }

    public function test_customer_can_open_analytics_with_period_filter(): void
    {
        foreach ([7, 30, 90, 365] as $period) {
            $this->actingAs($this->user)
                ->get(route('customer.sites.analytics', ['site' => $this->site, 'period' => $period]))
                ->assertOk();
        }
    }

    public function test_customer_can_deactivate_their_own_activation(): void
    {
        $activation = Activation::create([
            'license_id'    => $this->site->license_id,
            'domain'        => 'a.test',
            'site_url'      => 'https://a.test',
            'last_check_at' => now(),
            'status'        => 'active',
        ]);

        $this->actingAs($this->user)
            ->delete(route('customer.sites.deactivate-activation', [
                'site'       => $this->site,
                'activation' => $activation,
            ]))
            ->assertRedirect(route('customer.sites.show', $this->site));

        $this->assertSame('inactive', $activation->fresh()->status);
    }

    public function test_customer_cannot_deactivate_an_activation_from_another_license(): void
    {
        $other = Customer::create(['name' => 'Other', 'email' => 'o@o.test']);
        $otherLicense = License::create([
            'customer_id'     => $other->id,
            'license_key'     => 'OTHER-XXXX-XXXX-XXXX',
            'plan'            => 'starter',
            'max_activations' => 1,
            'expires_at'      => now()->addYear(),
            'status'          => 'active',
        ]);
        $otherActivation = Activation::create([
            'license_id'    => $otherLicense->id,
            'domain'        => 'attacker.test',
            'site_url'      => 'https://attacker.test',
            'last_check_at' => now(),
            'status'        => 'active',
        ]);

        // Pass our own site but the OTHER license's activation — must 404.
        $this->actingAs($this->user)
            ->delete(route('customer.sites.deactivate-activation', [
                'site'       => $this->site,
                'activation' => $otherActivation,
            ]))
            ->assertNotFound();

        $this->assertSame('active', $otherActivation->fresh()->status);
    }

    public function test_anonymous_users_get_redirected_to_login(): void
    {
        $this->get(route('customer.sites.index'))->assertRedirect(route('login'));
    }
}
