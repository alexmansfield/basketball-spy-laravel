<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use App\Services\BallDontLieService;
use Illuminate\Console\Command;

class SyncPlayers extends Command
{
    protected $signature = 'app:sync-players';

    protected $description = 'Sync active NBA players by cross-referencing BallDontLie with NBA Stats API';

    public function handle(BallDontLieService $api): int
    {
        $this->info('Syncing NBA players...');

        // Check API key
        $apiKey = config('services.balldontlie.key');
        if (empty($apiKey)) {
            $this->error('BALL_DONT_LIE_API_KEY is not configured!');
            return Command::FAILURE;
        }
        $this->info('✓ BALL_DONT_LIE_API_KEY is configured');

        // Check for teams
        $teamsByBdlId = Team::whereNotNull('balldontlie_id')
            ->get()
            ->keyBy('balldontlie_id');

        if ($teamsByBdlId->isEmpty()) {
            $this->error('No teams with balldontlie_id found. Run `php artisan app:sync-teams` first.');
            return Command::FAILURE;
        }
        $this->info("✓ Found " . $teamsByBdlId->count() . " teams with BallDontLie IDs");

        // Step 1: Get active player names from NBA Stats API (free)
        $this->info('Fetching active players from NBA Stats API...');
        $activePlayerNames = $api->getActivePlayerNamesFromNBAStats();

        if (empty($activePlayerNames)) {
            $this->error('Failed to fetch active players from NBA Stats API');
            $this->warn('The API may be temporarily unavailable. Try again later.');
            return Command::FAILURE;
        }
        $this->info("✓ Fetched " . count($activePlayerNames) . " active players from NBA Stats API");

        // Step 2: Reset all players to inactive
        Player::query()->update(['is_active' => false]);

        // Step 3: Iterate through BallDontLie players and cross-reference
        $this->info('Fetching and cross-referencing with BallDontLie API...');

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'active_matched' => 0];
        $cursor = null;
        $pageCount = 0;

        do {
            $response = $api->getPlayers($cursor, 100);
            $players = $response['data'] ?? [];
            $cursor = $response['meta']['next_cursor'] ?? null;
            $pageCount++;

            $this->output->write("\r  Processing page {$pageCount}... ");

            foreach ($players as $playerData) {
                $bdlId = $playerData['id'] ?? null;
                if (!$bdlId) {
                    $stats['skipped']++;
                    continue;
                }

                $teamBdlId = $playerData['team']['id'] ?? null;
                $team = $teamBdlId ? $teamsByBdlId->get($teamBdlId) : null;

                if (!$team) {
                    $stats['skipped']++;
                    continue;
                }

                // Build player name and check if active via NBA Stats API
                $fullName = trim(($playerData['first_name'] ?? '') . ' ' . ($playerData['last_name'] ?? ''));
                $normalizedName = $this->normalizeName($fullName);
                $isActive = isset($activePlayerNames[$normalizedName]);

                // Get NBA player ID if matched
                $nbaPlayerId = $isActive ? $activePlayerNames[$normalizedName] : null;

                $height = $playerData['height'] ?? null;
                $weight = isset($playerData['weight']) ? $playerData['weight'] . ' lbs' : null;

                $playerAttributes = [
                    'balldontlie_id' => $bdlId,
                    'team_id' => $team->id,
                    'name' => $fullName,
                    'jersey' => $playerData['jersey_number'] ?? '',
                    'position' => $playerData['position'] ?? '',
                    'height' => $height,
                    'weight' => $weight,
                    'is_active' => $isActive,
                    'extra_attributes' => [
                        'first_name' => $playerData['first_name'] ?? null,
                        'last_name' => $playerData['last_name'] ?? null,
                        'college' => $playerData['college'] ?? null,
                        'country' => $playerData['country'] ?? null,
                        'draft_year' => $playerData['draft_year'] ?? null,
                        'draft_round' => $playerData['draft_round'] ?? null,
                        'draft_number' => $playerData['draft_number'] ?? null,
                    ],
                ];

                // Also set NBA player ID if we found a match
                if ($nbaPlayerId) {
                    $playerAttributes['nba_player_id'] = $nbaPlayerId;
                }

                $player = Player::where('balldontlie_id', $bdlId)->first();

                if ($player) {
                    $player->update($playerAttributes);
                    $stats['updated']++;
                } else {
                    Player::create($playerAttributes);
                    $stats['created']++;
                }

                if ($isActive) {
                    $stats['active_matched']++;
                }
            }
        } while ($cursor !== null);

        $this->newLine(2);
        $this->info("✓ Players synced: {$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped");
        $this->info("✓ Active players matched: {$stats['active_matched']}");

        $activeCount = Player::where('is_active', true)->count();
        $this->info("Total active players in database: {$activeCount}");

        return Command::SUCCESS;
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
}
