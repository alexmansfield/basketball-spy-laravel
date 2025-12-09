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
    public int $timeout = 600; // 10 minutes - only active players now

    /**
     * Execute the job.
     */
    public function handle(BallDontLieService $api): void
    {
        Log::info('SyncPlayersFromBallDontLie: Starting active player sync');

        // Build team lookup by BallDontLie ID
        $teamsByBdlId = Team::whereNotNull('balldontlie_id')
            ->get()
            ->keyBy('balldontlie_id');

        if ($teamsByBdlId->isEmpty()) {
            Log::warning('SyncPlayersFromBallDontLie: No teams with balldontlie_id found. Run SyncTeamsFromBallDontLie first.');
            return;
        }

        // Get only active players (single API call, ~500 players)
        $activePlayers = $api->getActivePlayers();

        if (empty($activePlayers)) {
            Log::warning('SyncPlayersFromBallDontLie: No active players returned from API');
            return;
        }

        Log::info('SyncPlayersFromBallDontLie: Fetched active players', [
            'count' => count($activePlayers),
        ]);

        // Reset all players to inactive first
        Player::query()->update(['is_active' => false]);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0];

        foreach ($activePlayers as $playerData) {
            $stats['total']++;
            $bdlId = $playerData['id'] ?? null;

            if (!$bdlId) {
                $stats['skipped']++;
                continue;
            }

            // Get team from player data
            $teamBdlId = $playerData['team']['id'] ?? null;
            $team = $teamBdlId ? $teamsByBdlId->get($teamBdlId) : null;

            if (!$team) {
                // Player has no valid team assignment
                Log::debug('SyncPlayersFromBallDontLie: Player has no valid team', [
                    'player_id' => $bdlId,
                    'player_name' => ($playerData['first_name'] ?? '') . ' ' . ($playerData['last_name'] ?? ''),
                    'team_bdl_id' => $teamBdlId,
                ]);
                $stats['skipped']++;
                continue;
            }

            // Format height from feet/inches
            $height = null;
            if (isset($playerData['height'])) {
                $height = $playerData['height']; // API returns as string like "6-10"
            }

            // Format weight
            $weight = isset($playerData['weight']) ? $playerData['weight'] . ' lbs' : null;

            $playerAttributes = [
                'balldontlie_id' => $bdlId,
                'team_id' => $team->id,
                'name' => trim(($playerData['first_name'] ?? '') . ' ' . ($playerData['last_name'] ?? '')),
                'jersey' => $playerData['jersey_number'] ?? '',
                'position' => $playerData['position'] ?? '',
                'height' => $height,
                'weight' => $weight,
                'is_active' => true,
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

            // Find existing player by BallDontLie ID
            $player = Player::where('balldontlie_id', $bdlId)->first();

            if ($player) {
                // Update existing player - this will fix wrong team assignments!
                $player->update($playerAttributes);
                $stats['updated']++;
            } else {
                // Create new player
                Player::create($playerAttributes);
                $stats['created']++;
            }
        }

        Log::info('SyncPlayersFromBallDontLie: Sync completed', $stats);
    }
}
