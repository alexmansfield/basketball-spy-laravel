<?php

namespace App\Jobs;

use App\Services\NBAScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncGamesFromLLM implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 120;

    protected int $days;

    public function __construct(int $days = 3)
    {
        $this->days = $days;
    }

    public function handle(NBAScheduleService $service): void
    {
        Log::info('SyncGamesFromLLM: Starting (saved prompt returns 7 days)');

        try {
            // Saved prompt returns 7 days at once - only need one API call
            $games = $service->fetchGamesForDate(now()->format('Y-m-d'));

            if (!empty($games)) {
                $stored = $service->storeGames($games);

                // Clear cache for all dates in the response
                $dates = collect($games)->pluck('scheduled_at')->map(fn($dt) => $dt->format('Y-m-d'))->unique();
                foreach ($dates as $date) {
                    Cache::forget("games:date:{$date}");
                }

                Log::info('SyncGamesFromLLM: Completed', ['games_stored' => $stored]);
            } else {
                Log::warning('SyncGamesFromLLM: No games returned');
            }
        } catch (\Exception $e) {
            Log::error('SyncGamesFromLLM: Failed', ['error' => $e->getMessage()]);
        }
    }
}
