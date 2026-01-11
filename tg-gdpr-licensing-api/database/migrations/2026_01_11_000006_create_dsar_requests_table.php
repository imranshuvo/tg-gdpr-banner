<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsar_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            
            // Request type (GDPR Articles 15-22)
            $table->enum('request_type', [
                'access',       // Art. 15 - Right of access
                'erasure',      // Art. 17 - Right to erasure
                'rectification', // Art. 16 - Right to rectification
                'portability',  // Art. 20 - Right to data portability
                'restriction',  // Art. 18 - Right to restriction
                'objection'     // Art. 21 - Right to object
            ]);
            
            // Requester information
            $table->string('requester_email');
            $table->string('requester_name')->nullable();
            $table->string('requester_phone', 20)->nullable();
            $table->text('additional_info')->nullable();
            
            // Verification
            $table->string('verification_token', 64)->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_method')->nullable(); // email, id_upload, etc.
            
            // Processing status
            $table->enum('status', [
                'pending_verification',
                'verified',
                'processing',
                'awaiting_info',
                'completed',
                'rejected',
                'cancelled'
            ])->default('pending_verification');
            
            // Response data
            $table->string('data_export_path', 500)->nullable();
            $table->timestamp('export_expires_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            
            // Processing info
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            
            // SLA tracking (must respond within 30 days)
            $table->timestamp('due_date')->nullable();
            $table->boolean('sla_breached')->default(false);
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('requester_email');
            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsar_requests');
    }
};
