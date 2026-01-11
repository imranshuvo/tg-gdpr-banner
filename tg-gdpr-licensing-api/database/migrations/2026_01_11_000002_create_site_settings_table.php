<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            
            // Banner Appearance
            $table->enum('banner_position', ['bottom', 'top', 'bottom-left', 'bottom-right', 'center'])->default('bottom');
            $table->enum('banner_layout', ['bar', 'box', 'popup'])->default('bar');
            $table->string('primary_color', 7)->default('#1e40af');
            $table->string('accent_color', 7)->default('#3b82f6');
            $table->string('text_color', 7)->default('#1f2937');
            $table->string('bg_color', 7)->default('#ffffff');
            $table->string('button_style', 20)->default('rounded'); // rounded, square, pill
            
            // Banner Content
            $table->string('heading')->default('We value your privacy');
            $table->text('message')->nullable();
            $table->string('accept_all_text', 50)->default('Accept All');
            $table->string('reject_all_text', 50)->default('Reject All');
            $table->string('customize_text', 50)->default('Customize');
            $table->string('save_preferences_text', 50)->default('Save Preferences');
            $table->string('privacy_policy_url', 500)->nullable();
            $table->string('privacy_policy_text', 100)->default('Privacy Policy');
            
            // Category Labels & Descriptions
            $table->json('category_labels')->nullable(); // {necessary: "Essential", analytics: "Analytics", ...}
            $table->json('category_descriptions')->nullable();
            
            // Behavior Settings
            $table->boolean('show_reject_all')->default(true);
            $table->boolean('show_close_button')->default(false);
            $table->boolean('close_on_scroll')->default(false);
            $table->boolean('close_on_timeout')->default(false);
            $table->unsignedInteger('timeout_seconds')->default(0);
            $table->boolean('reload_on_consent')->default(false);
            $table->unsignedInteger('consent_expiry_days')->default(365);
            $table->unsignedInteger('reconsent_days')->default(365); // Force re-consent after X days
            
            // Script Blocking
            $table->boolean('auto_block_scripts')->default(true);
            $table->json('custom_script_patterns')->nullable(); // Additional patterns
            $table->json('script_whitelist')->nullable(); // Never block these
            
            // Advanced
            $table->boolean('respect_dnt')->default(false); // Do Not Track header
            $table->boolean('log_consents')->default(true);
            $table->string('custom_css', 5000)->nullable();
            $table->string('custom_js', 5000)->nullable();
            
            // TCF Settings
            $table->json('tcf_purposes')->nullable(); // Enabled purposes
            $table->json('tcf_vendors')->nullable(); // Enabled vendors
            $table->json('tcf_legitimate_interests')->nullable();
            
            // Google Consent Mode Settings
            $table->json('gcm_default_state')->nullable(); // Default consent state
            $table->boolean('gcm_wait_for_update')->default(true);
            $table->unsignedInteger('gcm_wait_timeout_ms')->default(500);
            $table->json('gcm_region_settings')->nullable(); // Per-region defaults
            
            $table->timestamps();
            
            $table->unique('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
