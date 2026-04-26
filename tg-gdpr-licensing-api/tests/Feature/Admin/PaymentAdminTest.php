<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_payment_settings_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.payments'))
            ->assertOk()
            ->assertSee('Stripe')
            ->assertSee('Frisbii')
            ->assertSee('Webhook URL', false);
    }

    public function test_payment_settings_can_be_saved_and_secret_is_encrypted(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.payments.update', 'stripe'), [
                'enabled'      => '1',
                'mode'         => 'test',
                'test_public'  => 'pk_test_AAA',
                'test_secret'  => 'sk_test_BBB',
                'test_webhook' => 'whsec_CCC',
            ])
            ->assertRedirect(route('admin.settings.payments'));

        $this->assertSame('pk_test_AAA', SystemSetting::get('payment.stripe.test.public'));
        $this->assertSame('sk_test_BBB', SystemSetting::get('payment.stripe.test.secret'));
        // Encrypted-at-rest: the raw row must not equal the cleartext.
        $rawSecret = SystemSetting::where('key', 'payment.stripe.test.secret')->value('value');
        $this->assertNotSame('sk_test_BBB', $rawSecret);
    }

    public function test_blank_secret_input_does_not_overwrite_existing_secret(): void
    {
        SystemSetting::set('payment.stripe.test.secret', 'sk_test_ORIGINAL', SystemSetting::TYPE_STRING, encrypt: true);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.payments.update', 'stripe'), [
                'enabled'     => '1',
                'mode'        => 'test',
                'test_public' => 'pk_test_NEW',
                'test_secret' => '', // intentionally blank — keep existing
            ])
            ->assertRedirect();

        $this->assertSame('sk_test_ORIGINAL', SystemSetting::get('payment.stripe.test.secret'));
    }

    public function test_plans_index_lists_seeded_plans(): void
    {
        Plan::create([
            'slug' => 'mvp', 'name' => 'MVP Plan', 'max_sites' => 1, 'features' => [],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee('MVP Plan');
    }

    public function test_plan_can_be_created_with_features_textarea(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.plans.store'), [
                'slug'                 => 'starter-test',
                'name'                 => 'Starter Test',
                'max_sites'            => 1,
                'display_price'        => '$49',
                'features_raw'         => "1 site\nCookie banner\n\nEmail support",
                'stripe_price_id_test' => 'price_abc',
                'is_active'            => '1',
            ])
            ->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('slug', 'starter-test')->firstOrFail();
        $this->assertSame(['1 site', 'Cookie banner', 'Email support'], $plan->features);
        $this->assertSame('price_abc', $plan->stripe_price_id_test);
    }

    public function test_non_admin_cannot_reach_payment_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.settings.payments'))
            ->assertForbidden();
    }
}
