<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('teams', function (Blueprint $table) {
                $table->string('league', 50)->default('NBA')->change();
            });
        } elseif (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE teams MODIFY league VARCHAR(50) NOT NULL DEFAULT 'NBA'");
        } else {
            Schema::table('teams', function (Blueprint $table) {
                $table->string('league', 50)->default('NBA')->change();
            });
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->index(['league', 'abbreviation'], 'teams_league_abbreviation_index');
        });

        Schema::create('external_ids', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('provider', 50);
            $table->string('provider_league', 50);
            $table->string('external_id', 100);
            $table->timestamps();

            $table->unique(
                ['entity_type', 'provider', 'provider_league', 'external_id'],
                'external_ids_provider_identity_unique'
            );
            $table->index(['entity_type', 'entity_id'], 'external_ids_entity_index');
            $table->index(['provider', 'provider_league'], 'external_ids_provider_league_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_ids');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('teams_league_abbreviation_index');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE teams MODIFY league ENUM('NBA', 'WNBA', 'Foreign') NOT NULL DEFAULT 'NBA'");
        }
    }
};
