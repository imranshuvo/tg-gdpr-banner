<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index on activations.status.
 *
 * License::canActivate() runs `where status = 'active'`->count() on every
 * activation attempt. Without this index it's a partial scan per call.
 * Composite (license_id, status) is more useful than just (status) since
 * the count is always scoped to a license.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('activations', function (Blueprint $t) {
            $t->index(['license_id', 'status'], 'activations_license_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activations', function (Blueprint $t) {
            $t->dropIndex('activations_license_status_idx');
        });
    }
};
