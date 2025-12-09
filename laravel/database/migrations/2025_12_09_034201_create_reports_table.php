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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Scout who created report
            $table->foreignId('team_id_at_time')->constrained('teams'); // Team player belonged to when report was made

            // OFFENSE ratings (1-5 scale, nullable for partial reports)
            $table->unsignedTinyInteger('offense_shooting')->nullable();
            $table->unsignedTinyInteger('offense_finishing')->nullable();
            $table->unsignedTinyInteger('offense_driving')->nullable();
            $table->unsignedTinyInteger('offense_dribbling')->nullable();
            $table->unsignedTinyInteger('offense_creating')->nullable();
            $table->unsignedTinyInteger('offense_passing')->nullable();

            // DEFENSE ratings (1-5 scale, nullable for partial reports)
            $table->unsignedTinyInteger('defense_one_on_one')->nullable();
            $table->unsignedTinyInteger('defense_blocking')->nullable();
            $table->unsignedTinyInteger('defense_team_defense')->nullable();
            $table->unsignedTinyInteger('defense_rebounding')->nullable();

            // INTANGIBLES ratings (1-5 scale, nullable for partial reports)
            $table->unsignedTinyInteger('intangibles_effort')->nullable();
            $table->unsignedTinyInteger('intangibles_role_acceptance')->nullable();
            $table->unsignedTinyInteger('intangibles_iq')->nullable();
            $table->unsignedTinyInteger('intangibles_awareness')->nullable();

            // ATHLETICISM ratings (1-5 scale, nullable for partial reports)
            $table->unsignedTinyInteger('athleticism_hands')->nullable();
            $table->unsignedTinyInteger('athleticism_length')->nullable();
            $table->unsignedTinyInteger('athleticism_quickness')->nullable();
            $table->unsignedTinyInteger('athleticism_jumping')->nullable();
            $table->unsignedTinyInteger('athleticism_strength')->nullable();
            $table->unsignedTinyInteger('athleticism_coordination')->nullable();

            // Additional fields
            $table->text('notes')->nullable(); // Free-form scout notes
            $table->timestamp('synced_at')->nullable(); // For local-first sync tracking

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance and analytics
            $table->index('player_id');
            $table->index('user_id');
            $table->index(['player_id', 'user_id']);
            $table->index('created_at');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
