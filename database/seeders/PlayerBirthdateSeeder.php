<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PlayerBirthdateSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(database_path('seeders/dobs.json'));
        $players = json_decode($json, true);

        $updated = 0;
        $notFound = 0;

        foreach ($players as $data) {
            $player = Player::find($data['id']);
            if ($player) {
                $player->update(['birthdate' => $data['birthdate']]);
                $updated++;
            } else {
                $this->command->warn("Player not found: {$data['id']} - {$data['name']}");
                $notFound++;
            }
        }

        $this->command->info("Updated {$updated} players with birthdates.");
        if ($notFound > 0) {
            $this->command->warn("{$notFound} players not found.");
        }
    }
}
