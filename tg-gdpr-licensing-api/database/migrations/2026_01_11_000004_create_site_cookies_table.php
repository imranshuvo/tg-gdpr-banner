<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global cookie database
        Schema::create('cookie_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('cookie_name');
            $table->string('cookie_pattern')->nullable(); // Regex pattern
            $table->boolean('is_regex')->default(false);
            $table->enum('category', ['necessary', 'functional', 'analytics', 'marketing']);
            $table->string('provider')->nullable();
            $table->string('provider_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->string('duration', 100)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('platform', 100)->nullable(); // Google Analytics, Facebook, etc.
            $table->enum('source', ['open_database', 'scanned', 'manual', 'ai_categorized'])->default('manual');
            $table->decimal('confidence_score', 3, 2)->default(1.00);
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
            
            $table->unique(['cookie_name', 'provider']);
            $table->index('category');

            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['cookie_name', 'provider', 'description']);
            }
        });
        
        // Site-specific cookies (overrides or custom)
        Schema::create('site_cookies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('cookie_definition_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('cookie_name');
            $table->string('cookie_pattern')->nullable();
            $table->boolean('is_regex')->default(false);
            $table->enum('category', ['necessary', 'functional', 'analytics', 'marketing']);
            $table->string('provider')->nullable();
            $table->text('description')->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('script_pattern', 500)->nullable(); // Pattern to block
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false); // True if added manually
            $table->enum('source', ['scan', 'manual', 'global_db'])->default('manual');
            $table->timestamp('last_detected_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['site_id', 'cookie_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_cookies');
        Schema::dropIfExists('cookie_definitions');
    }
};
