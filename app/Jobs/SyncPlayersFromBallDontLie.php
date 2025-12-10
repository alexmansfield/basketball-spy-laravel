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
    public int $timeout = 300; // 5 minutes - single API call for active players

    /**
     * Execute the job.
     * Syncs active players from BallDontLie API (requires ALL-STAR tier).
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

        // Get active players from BallDontLie (requires ALL-STAR tier)
        $activePlayers = $api->getAllActivePlayers();

        if (empty($activePlayers)) {
            Log::error('SyncPlayersFromBallDontLie: No active players returned. Check API subscription tier.');
            return;
        }

        Log::info('SyncPlayersFromBallDontLie: Fetched active players', [
            'count' => count($activePlayers),
        ]);

        // Reset all players to inactive
        Player::query()->update(['is_active' => false]);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($activePlayers as $playerData) {
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

            $height = $this->formatHeight($playerData['height'] ?? null);
            $weight = isset($playerData['weight']) ? $playerData['weight'] . ' lbs' : null;

            // BallDontLie ID is the same as NBA player ID - use it for headshots
            $headshotUrl = "https://cdn.nba.com/headshots/nba/latest/1040x760/{$bdlId}.png";

            $playerAttributes = [
                'balldontlie_id' => $bdlId,
                'nba_player_id' => $bdlId, // BallDontLie ID = NBA player ID
                'team_id' => $team->id,
                'name' => trim(($playerData['first_name'] ?? '') . ' ' . ($playerData['last_name'] ?? '')),
                'jersey' => $playerData['jersey_number'] ?? '',
                'position' => $playerData['position'] ?? '',
                'height' => $height,
                'weight' => $weight,
                'headshot_url' => $headshotUrl,
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

            $player = Player::where('balldontlie_id', $bdlId)->first();

            if ($player) {
                $player->update($playerAttributes);
                $stats['updated']++;
            } else {
                Player::create($playerAttributes);
                $stats['created']++;
            }
        }

        Log::info('SyncPlayersFromBallDontLie: Sync completed', $stats);
    }

    /**
     * Format height from "6-8" to "6'8\"" format.
     */
    protected function formatHeight(?string $height): ?string
    {
        if (!$height) {
            return null;
        }

        // Convert "6-8" to "6'8\""
        if (preg_match('/^(\d+)-(\d+)$/', $height, $matches)) {
            return $matches[1] . "'" . $matches[2] . '"';
        }

        return $height;
    }
}
