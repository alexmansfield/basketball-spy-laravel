<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use App\Services\BallDontLieService;
use Illuminate\Console\Command;

class SyncPlayers extends Command
{
    protected $signature = 'app:sync-players';

    protected $description = 'Sync active NBA players from BallDontLie API (requires ALL-STAR tier)';

    public function handle(BallDontLieService $api): int
    {
        $this->info('Syncing active NBA players from BallDontLie API...');

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

        // Get active players from BallDontLie (requires ALL-STAR tier - $9.99/month)
        $this->info('Fetching active players from BallDontLie API...');
        $activePlayers = $api->getAllActivePlayers();

        if (empty($activePlayers)) {
            $this->error('No active players returned from API.');
            $this->warn('Make sure you have the ALL-STAR tier subscription ($9.99/month).');
            $this->warn('The /players/active endpoint requires a paid subscription.');
            return Command::FAILURE;
        }

        $this->info("✓ Fetched " . count($activePlayers) . " active players");

        // Reset all players to inactive
        Player::query()->update(['is_active' => false]);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $bar = $this->output->createProgressBar(count($activePlayers));
        $bar->start();

        foreach ($activePlayers as $playerData) {
            $bdlId = $playerData['id'] ?? null;

            if (!$bdlId) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            $teamBdlId = $playerData['team']['id'] ?? null;
            $team = $teamBdlId ? $teamsByBdlId->get($teamBdlId) : null;

            if (!$team) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            $height = $playerData['height'] ?? null;
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

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Players synced: {$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped");

        $activeCount = Player::where('is_active', true)->count();
        $this->info("Active players in database: {$activeCount}");

        return Command::SUCCESS;
    }
}
