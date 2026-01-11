<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->default('contact_form'); // contact_form, free_download, pricing_page
            $table->string('status')->default('new'); // new, contacted, converted, closed
            $table->timestamps();
            
            $table->index('email');
            $table->index('source');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
