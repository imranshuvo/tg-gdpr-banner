<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            
            // Unique consent identifier
            $table->uuid('consent_id')->unique();
            
            // Anonymized visitor identification (GDPR compliant)
            $table->string('visitor_hash', 64)->index(); // SHA256 of IP+UA
            $table->string('ip_anonymized', 45)->nullable(); // Last octet zeroed: 192.168.1.0
            $table->string('country_code', 2)->nullable();
            $table->string('region_code', 10)->nullable();
            
            // Consent data
            $table->json('consent_categories'); // {necessary: true, analytics: false, ...}
            $table->enum('consent_method', ['accept_all', 'reject_all', 'customize', 'implicit'])->default('customize');
            
            // TCF Data
            $table->text('tcf_string')->nullable();
            $table->json('tcf_purposes')->nullable();
            $table->json('tcf_vendors')->nullable();
            $table->json('tcf_legitimate_interests')->nullable();
            
            // Google Consent Mode State
            $table->json('gcm_state')->nullable();
            
            // Policy version at time of consent
            $table->unsignedInteger('policy_version')->default(1);
            
            // Device info (anonymized)
            $table->string('user_agent_hash', 64)->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');
            $table->string('browser', 50)->nullable();
            
            // Consent lifecycle
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_reason')->nullable();
            
            // Sync status
            $table->boolean('synced_from_plugin')->default(false);
            $table->timestamp('plugin_created_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for reporting
            $table->index(['site_id', 'created_at']);
            $table->index(['site_id', 'consent_method']);
            $table->index(['country_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
