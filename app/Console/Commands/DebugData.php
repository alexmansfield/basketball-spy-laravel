<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DebugData extends Command
{
    protected $signature = 'app:debug
                            {check : What to check (arenas|arenas-missing|games|games-date|games-count)}
                            {--date= : Date for games-date check (YYYY-MM-DD, defaults to today ET)}';

    protected $description = 'Debug data integrity checks';

    public function handle(): int
    {
        return match ($this->argument('check')) {
            'arenas' => $this->checkArenas(),
            'arenas-missing' => $this->checkArenasMissing(),
            'games' => $this->checkGames(),
            'games-date' => $this->checkGamesDate(),
            'games-count' => $this->checkGamesCount(),
            default => $this->showHelp(),
        };
    }

    protected function checkArenas(): int
    {
        $this->info('Arena coordinates for all teams:');
        $this->newLine();

        Team::orderBy('abbreviation')->get()->each(function ($team) {
            $lat = $team->arena_latitude ?? 'NULL';
            $lng = $team->arena_longitude ?? 'NULL';
            $hasCoords = $team->arena_latitude && $team->arena_longitude;
            $status = $hasCoords ? '✓' : '✗';

            $this->line("{$status} {$team->abbreviation}: {$team->arena_name} ({$lat}, {$lng})");
        });

        return 0;
    }

    protected function checkArenasMissing(): int
    {
        $missing = Team::where(function ($q) {
            $q->whereNull('arena_latitude')
              ->orWhere('arena_latitude', 0)
              ->orWhereNull('arena_longitude')
              ->orWhere('arena_longitude', 0);
        })->get();

        $total = Team::count();

        if ($missing->isEmpty()) {
            $this->info("✓ All {$total} teams have arena coordinates");
            return 0;
        }

        $this->warn("Missing coordinates: {$missing->count()}/{$total} teams");
        $this->newLine();

        $missing->each(fn($t) => $this->line("  • {$t->abbreviation} - {$t->nickname}"));

        return 1;
    }

    protected function checkGames(): int
    {
        $this->info('Next 10 upcoming games:');
        $this->newLine();

        $games = Game::with(['homeTeam', 'awayTeam'])
            ->where('scheduled_at', '>=', now()->subHours(4))
            ->orderBy('scheduled_at')
            ->take(10)
            ->get();

        if ($games->isEmpty()) {
            $this->warn('No upcoming games found');
            return 1;
        }

        foreach ($games as $game) {
            $matchup = "{$game->awayTeam->abbreviation} @ {$game->homeTeam->abbreviation}";
            $utc = $game->scheduled_at->format('Y-m-d H:i:s');
            $et = $game->scheduled_at->setTimezone('America/New_York')->format('M j g:i A T');
            $pt = $game->scheduled_at->setTimezone('America/Los_Angeles')->format('g:i A T');

            $this->line(str_pad($matchup, 12) . " | UTC: {$utc} | {$et} | {$pt}");
            $this->line("             external_id: {$game->external_id}");
            $this->newLine();
        }

        return 0;
    }

    protected function checkGamesDate(): int
    {
        $dateStr = $this->option('date') ?? now('America/New_York')->toDateString();

        $start = Carbon::parse($dateStr, 'America/New_York')->startOfDay()->utc();
        $end = Carbon::parse($dateStr, 'America/New_York')->endOfDay()->utc();

        $this->info("Games for {$dateStr} (ET)");
        $this->line("UTC range: {$start} to {$end}");
        $this->newLine();

        $games = Game::with(['homeTeam', 'awayTeam'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();

        if ($games->isEmpty()) {
            $this->warn('No games found for this date');
            return 1;
        }

        $this->info("Found {$games->count()} games:");
        $this->newLine();

        foreach ($games as $game) {
            $matchup = "{$game->awayTeam->abbreviation} @ {$game->homeTeam->abbreviation}";
            $time = $game->scheduled_at->setTimezone('America/New_York')->format('g:i A T');
            $this->line("  • {$matchup} - {$time}");
        }

        return 0;
    }

    protected function checkGamesCount(): int
    {
        $this->info('Games count by date (ET):');
        $this->newLine();

        $games = Game::where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn($g) => $g->scheduled_at->setTimezone('America/New_York')->format('Y-m-d'));

        if ($games->isEmpty()) {
            $this->warn('No upcoming games found');
            return 1;
        }

        foreach ($games as $date => $dateGames) {
            $dayName = Carbon::parse($date)->format('D');
            $this->line("{$date} ({$dayName}): {$dateGames->count()} games");
        }

        $this->newLine();
        $this->info("Total: {$games->flatten()->count()} games");

        return 0;
    }

    protected function showHelp(): int
    {
        $this->error('Unknown check. Available checks:');
        $this->newLine();
        $this->line('  arenas         - Show arena coordinates for all teams');
        $this->line('  arenas-missing - Show teams missing arena coordinates');
        $this->line('  games          - Show next 10 upcoming games with times');
        $this->line('  games-date     - Show games for a specific date (--date=YYYY-MM-DD)');
        $this->line('  games-count    - Show game count by date');
        $this->newLine();
        $this->line('Examples:');
        $this->line('  php artisan app:debug arenas-missing');
        $this->line('  php artisan app:debug games-date --date=2024-12-14');

        return 1;
    }
}
