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

        // Create test user (optional - can be commented out in production)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
