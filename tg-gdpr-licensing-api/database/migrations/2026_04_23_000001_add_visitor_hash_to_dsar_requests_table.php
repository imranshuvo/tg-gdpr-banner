<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dsar_requests', function (Blueprint $table) {
            $table->string('visitor_hash', 64)->nullable()->after('additional_info');
            $table->index('visitor_hash');
        });
    }

    public function down(): void
    {
        Schema::table('dsar_requests', function (Blueprint $table) {
            $table->dropIndex(['visitor_hash']);
            $table->dropColumn('visitor_hash');
        });
    }
};