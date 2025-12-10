<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncHeadshotsFromFile extends Command
{
    protected $signature = 'app:sync-headshots';

    protected $description = 'Sync NBA player headshots from local JSON file (matches by name)';

    public function handle(): int
    {
        $this->info('Syncing NBA player headshots from JSON file...');

        $filePath = database_path('data/nba_player_headshots.json');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $json = file_get_contents($filePath);
        $headshots = json_decode($json, true);

        if (!is_array($headshots)) {
            $this->error('Invalid JSON format');
            return Command::FAILURE;
        }

        $this->info("Loaded " . count($headshots) . " players from JSON file");

        // Build lookup by normalized name
        $headshotsByName = [];
        foreach ($headshots as $player) {
            $normalizedName = $this->normalizeName($player['full_name']);
            $headshotsByName[$normalizedName] = [
                'nba_player_id' => $player['player_id'],
                'headshot_url' => $player['headshot_url'],
            ];
        }

        // Get all active players from database
        $activePlayers = Player::where('is_active', true)->get();
        $this->info("Found " . $activePlayers->count() . " active players in database");

        $stats = ['matched' => 0, 'unmatched' => 0];
        $unmatched = [];

        $bar = $this->output->createProgressBar($activePlayers->count());
        $bar->start();

        foreach ($activePlayers as $player) {
            $normalizedName = $this->normalizeName($player->name);

            if (isset($headshotsByName[$normalizedName])) {
                $data = $headshotsByName[$normalizedName];
                $player->update([
                    'nba_player_id' => $data['nba_player_id'],
                    'headshot_url' => $data['headshot_url'],
                ]);
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
                $unmatched[] = $player->name;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Matched: {$stats['matched']} players");

        if ($stats['unmatched'] > 0) {
            $this->warn("Unmatched: {$stats['unmatched']} players");
            $this->line("Unmatched players:");
            foreach (array_slice($unmatched, 0, 20) as $name) {
                $this->line("  - {$name}");
            }
            if (count($unmatched) > 20) {
                $this->line("  ... and " . (count($unmatched) - 20) . " more");
            }
        }

        // Clear player cache
        $this->info('Clearing player cache...');
        $keys = Cache::get('players:*');
        // Just flush all cache to be safe
        Cache::flush();
        $this->info('Cache cleared');

        return Command::SUCCESS;
    }

    /**
     * Normalize a player name for matching.
     */
    protected function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        // Remove suffixes like Jr., Sr., II, III, IV
        $name = preg_replace('/\s+(jr\.?|sr\.?|ii|iii|iv)$/i', '', $name);
        // Remove periods
        $name = str_replace('.', '', $name);
        // Normalize whitespace
        $name = preg_replace('/\s+/', ' ', $name);
        // Transliterate accented characters
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        return trim($name);
    }
}
