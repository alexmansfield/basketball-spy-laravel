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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('jersey', 10);
            $table->string('position', 10); // G, F, C, etc.
            $table->string('height', 10)->nullable(); // e.g., "6'2\""
            $table->string('weight', 10)->nullable(); // e.g., "185 lbs"
            $table->string('headshot_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('team_id');
            $table->index(['team_id', 'jersey']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
