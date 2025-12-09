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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Full team name: "Golden State Warriors"
            $table->string('abbreviation', 10); // Team abbreviation: "GSW"
            $table->string('location'); // City/region: "Golden State"
            $table->string('nickname'); // Team nickname: "Warriors"
            $table->enum('league', ['NBA', 'WNBA', 'Foreign'])->default('NBA');
            $table->string('url')->nullable(); // Official team website
            $table->string('logo_url')->nullable(); // CDN URL for team logo
            $table->string('color', 7)->nullable(); // Primary team color (hex): "#1d428a"
            $table->timestamps();
            $table->softDeletes(); // For soft delete support

            // Indexes for performance
            $table->index('league');
            $table->index('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
