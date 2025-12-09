<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed all basketball teams
        $this->call([
            NBATeamsSeeder::class,
            WNBATeamsSeeder::class,
            InternationalTeamsSeeder::class,
        ]);

        // Seed players for all NBA teams
        $this->call([
            PlayersSeeder::class,
        ]);

        // Seed admin and test user accounts
        $this->call([
            AdminSeeder::class,
        ]);
    }
}
