<?php

namespace App\Console\Commands;

use App\Jobs\SyncPlayersFromEspn;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Console\Command;

class SyncEspnRosters extends Command
{
    protected $signature = 'app:sync-espn-rosters';

    protected $description = 'Sync current NBA rosters from ESPN (no API key required)';

    public function handle(): int
    {
        $this->info('Syncing NBA rosters from ESPN...');

        if (Team::where('league', 'NBA')->doesntExist()) {
            $this->error('No NBA teams found. Seed NBA teams first.');

            return Command::FAILURE;
        }

        SyncPlayersFromEspn::dispatchSync();

        $this->info('✓ Sync complete. Active NBA players: '.Player::where('is_active', true)->count());

        return Command::SUCCESS;
    }
}
