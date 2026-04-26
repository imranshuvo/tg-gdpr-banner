<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `subscriptions` table was created by Cashier with Stripe-shaped columns.
 * We add three columns so Frisbii subscriptions can coexist in the same table:
 *   - provider:                'stripe' | 'frisbii'
 *   - mode:                    'test' | 'live' (locked at creation)
 *   - provider_subscription_id: provider's subscription id (Cashier's Stripe ID lives in stripe_id)
 *
 * Cashier-managed rows continue to populate stripe_id / stripe_status / stripe_price.
 * Frisbii-managed rows leave those NULL and use provider_subscription_id instead.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->string('provider')->default('stripe')->after('type');
            $t->string('mode')->default('live')->after('provider');
            $t->string('provider_subscription_id')->nullable()->after('mode');
            $t->foreignId('plan_id')->nullable()->after('provider_subscription_id')->constrained('plans')->nullOnDelete();

            // Stripe rows are uniquely keyed by stripe_id (existing). Frisbii rows
            // are uniquely keyed by (provider, provider_subscription_id).
            $t->unique(['provider', 'provider_subscription_id'], 'subscriptions_provider_sub_unique');
            $t->index(['provider', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->dropUnique('subscriptions_provider_sub_unique');
            $t->dropIndex(['provider', 'mode']);
            $t->dropForeign(['plan_id']);
            $t->dropColumn(['provider', 'mode', 'provider_subscription_id', 'plan_id']);
        });
    }
};
