<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Team;
use App\Services\BallDontLieService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPlayersFromBallDontLie implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 900; // 15 minutes - paginating all players with rate limits

    /**
     * Execute the job.
     * Cross-references BallDontLie players with NBA Stats API to determine active status.
     */
    public function handle(BallDontLieService $api): void
    {
        Log::info('SyncPlayersFromBallDontLie: Starting player sync with NBA Stats cross-reference');

        // Build team lookup by BallDontLie ID
        $teamsByBdlId = Team::whereNotNull('balldontlie_id')
            ->get()
            ->keyBy('balldontlie_id');

        if ($teamsByBdlId->isEmpty()) {
            Log::warning('SyncPlayersFromBallDontLie: No teams with balldontlie_id found. Run SyncTeamsFromBallDontLie first.');
            return;
        }

        // Step 1: Get active player names from NBA Stats API (free)
        $activePlayerNames = $api->getActivePlayerNamesFromNBAStats();

        if (empty($activePlayerNames)) {
            Log::error('SyncPlayersFromBallDontLie: Failed to fetch active players from NBA Stats API');
            return;
        }

        Log::info('SyncPlayersFromBallDontLie: Fetched active players from NBA Stats API', [
            'count' => count($activePlayerNames),
        ]);

        // Step 2: Reset all players to inactive
        Player::query()->update(['is_active' => false]);

        // Step 3: Iterate through BallDontLie players and cross-reference
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'active_matched' => 0, 'pages' => 0];
        $cursor = null;

        do {
            $response = $api->getPlayers($cursor, 100);
            $players = $response['data'] ?? [];
            $cursor = $response['meta']['next_cursor'] ?? null;
            $stats['pages']++;

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

            // Log progress every 10 pages
            if ($stats['pages'] % 10 === 0) {
                Log::info('SyncPlayersFromBallDontLie: Progress', $stats);
            }
        } while ($cursor !== null);

        Log::info('SyncPlayersFromBallDontLie: Sync completed', $stats);
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
