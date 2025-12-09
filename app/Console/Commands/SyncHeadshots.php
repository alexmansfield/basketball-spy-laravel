<?php

namespace App\Console\Commands;

use App\Jobs\SyncPlayerHeadshots;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncHeadshots extends Command
{
    protected $signature = 'app:sync-headshots
                            {--queue : Dispatch job to queue instead of running synchronously}';

    protected $description = 'Sync player headshots from NBA.com by matching player names';

    /**
     * NBA Stats API endpoint for all players.
     */
    private const NBA_STATS_URL = 'https://stats.nba.com/stats/commonallplayers';

    /**
     * NBA CDN headshot URL pattern.
     */
    private const HEADSHOT_URL_PATTERN = 'https://cdn.nba.com/headshots/nba/latest/1040x760/%d.png';

    public function handle(): int
    {
        $useQueue = $this->option('queue');

        $this->info('Syncing player headshots from NBA.com...');

        if ($useQueue) {
            SyncPlayerHeadshots::dispatch();
            $this->info('Job dispatched to queue. Run `php artisan queue:work` to process.');
            return Command::SUCCESS;
        }

        $this->info('Running synchronously...');

        // Fetch all current NBA players from NBA Stats API
        $this->info('Fetching player list from NBA Stats API...');
        $nbaPlayers = $this->fetchNBAPlayers();

        if (empty($nbaPlayers)) {
            $this->error('Failed to fetch NBA players from stats.nba.com');
            $this->warn('The NBA Stats API may be blocking requests. Try again later or use --queue.');
            return Command::FAILURE;
        }

        $this->info("✓ Fetched " . count($nbaPlayers) . " players from NBA Stats API");

        // Build lookup by normalized name
        $nbaPlayerLookup = [];
        foreach ($nbaPlayers as $nbaPlayer) {
            $name = $this->normalizeName($nbaPlayer['display_name']);
            $nbaPlayerLookup[$name] = $nbaPlayer;
        }

        // Get only active players that need headshots
        $players = Player::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('nba_player_id')
                    ->orWhereNull('headshot_url');
            })
            ->get();

        $this->info("Processing " . $players->count() . " players...");

        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        $stats = ['matched' => 0, 'unmatched' => 0];
        $unmatched = [];

        foreach ($players as $player) {
            $normalizedName = $this->normalizeName($player->name);

            if (isset($nbaPlayerLookup[$normalizedName])) {
                $nbaPlayer = $nbaPlayerLookup[$normalizedName];
                $nbaPlayerId = $nbaPlayer['person_id'];
                $headshotUrl = sprintf(self::HEADSHOT_URL_PATTERN, $nbaPlayerId);

                $player->update([
                    'nba_player_id' => $nbaPlayerId,
                    'headshot_url' => $headshotUrl,
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

        $this->info("✓ Matched: {$stats['matched']} players");

        if ($stats['unmatched'] > 0) {
            $this->warn("✗ Unmatched: {$stats['unmatched']} players");

            if ($this->output->isVerbose()) {
                $this->line("Unmatched players:");
                foreach (array_slice($unmatched, 0, 20) as $name) {
                    $this->line("  - {$name}");
                }
                if (count($unmatched) > 20) {
                    $this->line("  ... and " . (count($unmatched) - 20) . " more");
                }
            }
        }

        $playersWithHeadshots = Player::whereNotNull('headshot_url')->count();
        $this->info("Total players with headshots: {$playersWithHeadshots}");

        return Command::SUCCESS;
    }

    /**
     * Fetch all current NBA players from the NBA Stats API.
     */
    protected function fetchNBAPlayers(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Referer' => 'https://www.nba.com/',
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Accept' => 'application/json',
                ])
                ->get(self::NBA_STATS_URL, [
                    'LeagueID' => '00',
                    'Season' => $this->getCurrentSeason(),
                    'IsOnlyCurrentSeason' => '1',
                ]);

            if (!$response->successful()) {
                $this->error("NBA API returned status: " . $response->status());
                return [];
            }

            $data = $response->json();

            $resultSet = $data['resultSets'][0] ?? [];
            $headers = $resultSet['headers'] ?? [];
            $rows = $resultSet['rowSet'] ?? [];

            $personIdIdx = array_search('PERSON_ID', $headers);
            $displayNameIdx = array_search('DISPLAY_FIRST_LAST', $headers);

            if ($personIdIdx === false || $displayNameIdx === false) {
                $this->error("Unexpected API response format");
                return [];
            }

            $players = [];
            foreach ($rows as $row) {
                $players[] = [
                    'person_id' => $row[$personIdIdx],
                    'display_name' => $row[$displayNameIdx],
                ];
            }

            return $players;

        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Normalize a player name for matching.
     */
    protected function normalizeName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/\s+(jr\.?|sr\.?|ii|iii|iv)$/i', '', $name);
        $name = str_replace('.', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        return trim($name);
    }

    /**
     * Get the current NBA season string (e.g., "2024-25").
     */
    protected function getCurrentSeason(): string
    {
        $year = (int) date('Y');
        $month = (int) date('n');

        if ($month < 10) {
            $year--;
        }

        $nextYear = substr((string) ($year + 1), -2);
        return "{$year}-{$nextYear}";
    }
}
