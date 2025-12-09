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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subscription_tier')->default('basic'); // basic, pro, enterprise
            $table->boolean('advanced_analytics_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Index for faster queries
            $table->index('subscription_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
