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
    public int $timeout = 1800; // 30 minutes - players take longer due to pagination

    /**
     * Execute the job.
     */
    public function handle(BallDontLieService $api): void
    {
        Log::info('SyncPlayersFromBallDontLie: Starting player sync');

        // Build team lookup by BallDontLie ID
        $teamsByBdlId = Team::whereNotNull('balldontlie_id')
            ->get()
            ->keyBy('balldontlie_id');

        if ($teamsByBdlId->isEmpty()) {
            Log::warning('SyncPlayersFromBallDontLie: No teams with balldontlie_id found. Run SyncTeamsFromBallDontLie first.');
            return;
        }

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0];

        foreach ($api->getAllPlayers() as $playerData) {
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

            // Log progress every 100 players
            if ($stats['total'] % 100 === 0) {
                Log::info('SyncPlayersFromBallDontLie: Progress', $stats);
            }
        }

        Log::info('SyncPlayersFromBallDontLie: Sync completed', $stats);

        // Now mark active players
        $this->markActivePlayers($api);
    }

    /**
     * Mark active players using the /players/active endpoint.
     */
    protected function markActivePlayers(BallDontLieService $api): void
    {
        Log::info('SyncPlayersFromBallDontLie: Fetching active players list');

        $activePlayers = $api->getActivePlayers();

        if (empty($activePlayers)) {
            Log::warning('SyncPlayersFromBallDontLie: No active players returned');
            return;
        }

        // Get all active player BallDontLie IDs
        $activeBdlIds = collect($activePlayers)->pluck('id')->toArray();

        Log::info('SyncPlayersFromBallDontLie: Marking active players', [
            'active_count' => count($activeBdlIds),
        ]);

        // Reset all players to inactive first
        Player::query()->update(['is_active' => false]);

        // Mark active players
        $updated = Player::whereIn('balldontlie_id', $activeBdlIds)
            ->update(['is_active' => true]);

        Log::info('SyncPlayersFromBallDontLie: Active players marked', [
            'marked_active' => $updated,
        ]);
    }
}
