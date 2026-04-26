<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();

            // Identity / display
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->json('features')->nullable();          // ["1 site license", "Cookie banner + auto-scanner", ...]
            $t->unsignedSmallInteger('max_sites');

            // Display only — actual money lives in the providers' Price/Plan objects.
            $t->string('display_price')->nullable();   // e.g. "$99"
            $t->string('display_period')->default('/year');

            // Provider-specific IDs (test + live mirror).
            $t->string('stripe_price_id_test')->nullable();
            $t->string('stripe_price_id_live')->nullable();
            $t->string('frisbii_plan_id_test')->nullable();
            $t->string('frisbii_plan_id_live')->nullable();

            // UX flags
            $t->boolean('is_popular')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);

            $t->timestamps();

            $t->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
