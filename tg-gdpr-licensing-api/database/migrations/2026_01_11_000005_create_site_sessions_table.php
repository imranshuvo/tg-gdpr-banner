<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily session aggregates for billing
        Schema::create('site_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->date('date');
            
            // Counts
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('banner_shown')->default(0);
            $table->unsignedInteger('consent_given')->default(0);
            $table->unsignedInteger('consent_denied')->default(0);
            $table->unsignedInteger('consent_customized')->default(0);
            $table->unsignedInteger('no_action')->default(0);
            
            // Category acceptance counts
            $table->unsignedInteger('accepted_functional')->default(0);
            $table->unsignedInteger('accepted_analytics')->default(0);
            $table->unsignedInteger('accepted_marketing')->default(0);
            
            // Geographic breakdown (JSON for flexibility)
            $table->json('geo_breakdown')->nullable();
            
            // Device breakdown
            $table->json('device_breakdown')->nullable();
            
            $table->timestamps();
            
            $table->unique(['site_id', 'date']);
            $table->index(['site_id', 'date']);
        });
        
        // Monthly billing aggregates
        Schema::create('site_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('year');
            $table->unsignedTinyInteger('month');
            
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_consents')->default(0);
            $table->unsignedInteger('session_limit')->default(25000);
            $table->boolean('limit_exceeded')->default(false);
            $table->timestamp('limit_exceeded_at')->nullable();
            
            $table->boolean('billed')->default(false);
            $table->timestamp('billed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['site_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_usage');
        Schema::dropIfExists('site_sessions');
    }
};
