<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('license_id')->nullable()->constrained()->onDelete('set null');
            
            // Site identification
            $table->string('domain')->index();
            $table->string('site_url', 500);
            $table->string('site_name')->nullable();
            $table->string('site_token', 64)->unique(); // API token for this site
            
            // Compliance settings
            $table->boolean('tcf_enabled')->default(true);
            $table->boolean('gcm_enabled')->default(true);
            $table->boolean('geo_targeting_enabled')->default(true);
            $table->json('geo_countries')->nullable(); // Countries to show banner
            
            // Policy versioning
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamp('policy_updated_at')->nullable();
            
            // Scan info
            $table->timestamp('last_scan_at')->nullable();
            $table->unsignedInteger('cookies_detected')->default(0);
            
            // Status
            $table->enum('status', ['active', 'paused', 'trial', 'expired', 'deleted'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['customer_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
