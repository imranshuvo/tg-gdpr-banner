<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HMAC-SHA256 signature on every consent record.
 *
 * Without this column a regulator-grade "prove this visitor consented" audit
 * fails — any DB row could be forged. Signature is computed from the canonical
 * payload (consent_id, site_id, visitor_hash, consent_categories, gcm_state,
 * tcf_string, policy_version, signed_at) using APP_KEY. signed_at is part of
 * the hashed payload, so tampering with it invalidates the signature.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('consent_records', function (Blueprint $t) {
            $t->char('signature', 64)->nullable()->after('synced_from_plugin');
            $t->timestamp('signed_at')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('consent_records', function (Blueprint $t) {
            $t->dropColumn(['signature', 'signed_at']);
        });
    }
};
