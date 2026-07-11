<?php

namespace App\Console\Commands;

use App\Jobs\SyncPlayersFromBallDontLie;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Console\Command;

class SyncPlayers extends Command
{
    protected $signature = 'app:sync-players';

    protected $description = 'Sync active NBA players from BallDontLie API (requires ALL-STAR tier)';

    public function handle(): int
    {
        $this->info('Syncing active NBA players from BallDontLie API...');

        if (empty(config('services.balldontlie.key'))) {
            $this->error('BALL_DONT_LIE_API_KEY is not configured!');

            return Command::FAILURE;
        }

        if (Team::whereNotNull('balldontlie_id')->doesntExist()) {
            $this->error('No teams with balldontlie_id found. Run `php artisan app:sync-teams` first.');

            return Command::FAILURE;
        }

        // Delegate to the hardened job (single source of truth): it scopes the
        // is_active reset to NBA teams and matches seeded players by nba_player_id.
        SyncPlayersFromBallDontLie::dispatchSync();

        $this->info('✓ Sync complete. Active NBA players: '.Player::where('is_active', true)->count());

        return Command::SUCCESS;
    }
}
