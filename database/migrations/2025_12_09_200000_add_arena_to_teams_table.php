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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('arena_name')->nullable()->after('color');
            $table->string('arena_city')->nullable()->after('arena_name');
            $table->string('arena_state', 2)->nullable()->after('arena_city');
            $table->decimal('arena_latitude', 10, 8)->nullable()->after('arena_state');
            $table->decimal('arena_longitude', 11, 8)->nullable()->after('arena_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'arena_name',
                'arena_city',
                'arena_state',
                'arena_latitude',
                'arena_longitude',
            ]);
        });
    }
};
