<?php

use App\Jobs\SyncGamesFromLLM;
use App\Jobs\SyncPlayerMinutes;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync games twice weekly (Mon & Thu at 6 AM ET) - prompt returns 7 days
Schedule::job(new SyncGamesFromLLM)
    ->days([1, 4]) // Monday, Thursday
    ->at('06:00')
    ->timezone('America/New_York')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SyncPlayerMinutes)
    ->daily()
    ->at('05:00')
    ->withoutOverlapping()
    ->onOneServer();

// Refresh NBA rosters weekly (Mon 4:30 AM ET) so trades/call-ups stop going stale
Schedule::command('app:import-sportradar-rosters nba')
    ->weeklyOn(1, '04:30')
    ->timezone('America/New_York')
    ->withoutOverlapping()
    ->onOneServer();
