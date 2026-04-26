<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge payment success → License creation.
 *
 * Two changes:
 *   1. plan column was an ENUM('single','3-sites','10-sites') — pre-Plan-table
 *      vocabulary that doesn't match the new seeded plans (starter, pro, agency).
 *      Convert to varchar so any plan slug fits. Existing values are preserved.
 *   2. Add plan_id (FK to plans) and provider_subscription_id (string) so the
 *      webhook handler can:
 *        - Read max_sites from the plan record (single source of truth)
 *        - Be idempotent: if a webhook is re-delivered, the existing License
 *          for this provider_subscription_id is returned, not duplicated.
 */
return new class extends Migration {
    public function up(): void
    {
        // MySQL: doctrine/dbal isn't a dep, so use raw SQL for the ENUM→VARCHAR change.
        // SQLite (test env) ignores ENUM and stores as TEXT, so the change is a no-op there.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE licenses MODIFY plan VARCHAR(64) NOT NULL"
            );
        }

        Schema::table('licenses', function (Blueprint $t) {
            $t->foreignId('plan_id')
                ->nullable()
                ->after('plan')
                ->constrained('plans')
                ->nullOnDelete();

            $t->string('provider_subscription_id')
                ->nullable()
                ->after('plan_id');

            // Webhook idempotency: at most one License per provider subscription.
            $t->unique('provider_subscription_id', 'licenses_provider_sub_unique');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $t) {
            $t->dropUnique('licenses_provider_sub_unique');
            $t->dropForeign(['plan_id']);
            $t->dropColumn(['plan_id', 'provider_subscription_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE licenses MODIFY plan ENUM('single','3-sites','10-sites') NOT NULL"
            );
        }
    }
};
