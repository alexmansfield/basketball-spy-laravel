<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use App\Jobs\SyncGames;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GamesController extends Controller
{
    /**
     * Cache TTL in seconds (5 minutes for games - updates frequently during game day)
     */
    private const CACHE_TTL = 300;

    /**
     * Get today's games.
     *
     * GET /api/games/today
     *
     * Fetches games from local DB first, falls back to SportsBlaze API if empty.
     */
    public function today(Request $request): JsonResponse
    {
        $date = now()->toDateString();
        return $this->getGamesForDate($date);
    }

    /**
     * Get games for a specific date.
     *
     * GET /api/games/{date}
     */
    public function byDate(string $date, Request $request): JsonResponse
    {
        return $this->getGamesForDate($date);
    }

    /**
     * Get games for a date, fetching from SportsBlaze API if DB is empty.
     */
    protected function getGamesForDate(string $date): JsonResponse
    {
        $cacheKey = "games:date:{$date}";

        // Allow cache bypass with ?fresh=1 for debugging
        if (request()->has('fresh')) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            // First, check local database
            $games = Game::with(['homeTeam', 'awayTeam'])
                ->forDate($date)
                ->orderBy('scheduled_at')
                ->get();

            // Debug: Log what we found
            Log::info('GamesController: DB query for date', [
                'date' => $date,
                'games_count' => $games->count(),
                'total_games_in_db' => Game::count(),
            ]);

            // If no games in DB, try fetching from SportsBlaze API
            if ($games->isEmpty()) {
                $games = $this->fetchGamesFromApi($date);
            }

            return [
                'games' => $games->map(function ($game) {
                    return $this->formatGame($game);
                })->toArray(),
                'date' => $date,
                'debug' => [
                    'total_games_in_db' => Game::count(),
                    'games_for_date' => $games->count(),
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * Fetch games from SportsBlaze API and store them.
     */
    protected function fetchGamesFromApi(string $date): \Illuminate\Database\Eloquent\Collection
    {
        $apiKey = config('services.sportsblaze.key');

        if (empty($apiKey)) {
            Log::warning('GamesController: SPORTSBLAZE_API_KEY not configured, returning empty games');
            return Game::query()->where('id', 0)->get(); // Empty collection
        }

        try {
            $response = Http::timeout(10)->get(
                "https://api.sportsblaze.com/nba/v1/games/{$date}/schedule.json",
                ['key' => $apiKey]
            );

            if (!$response->successful()) {
                Log::error('GamesController: SportsBlaze API request failed', [
                    'status' => $response->status(),
                    'date' => $date,
                ]);
                return Game::query()->where('id', 0)->get();
            }

            $data = $response->json();
            $this->storeGamesFromApi($data, $date);

            // Re-fetch from database with relationships
            return Game::with(['homeTeam', 'awayTeam'])
                ->forDate($date)
                ->orderBy('scheduled_at')
                ->get();

        } catch (\Exception $e) {
            Log::error('GamesController: Exception fetching from SportsBlaze', [
                'message' => $e->getMessage(),
                'date' => $date,
            ]);
            return Game::query()->where('id', 0)->get();
        }
    }

    /**
     * Store games from API response into database.
     */
    protected function storeGamesFromApi(array $data, string $date): void
    {
        $games = $data['games'] ?? [];
        $teams = Team::all()->keyBy(fn($t) => strtolower($t->abbreviation));

        foreach ($games as $gameData) {
            $externalId = $gameData['id'] ?? null;
            if (!$externalId) {
                continue;
            }

            // Find teams by abbreviation
            $homeAbbr = strtolower($gameData['home']['alias'] ?? $gameData['home']['abbreviation'] ?? '');
            $awayAbbr = strtolower($gameData['away']['alias'] ?? $gameData['away']['abbreviation'] ?? '');

            $homeTeam = $teams->get($homeAbbr);
            $awayTeam = $teams->get($awayAbbr);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('GamesController: Could not match teams', [
                    'home_abbr' => $homeAbbr,
                    'away_abbr' => $awayAbbr,
                ]);
                continue;
            }

            // Parse scheduled time
            $scheduledAt = isset($gameData['scheduled'])
                ? Carbon::parse($gameData['scheduled'])
                : Carbon::parse("{$date} " . ($gameData['time'] ?? '19:00:00'));

            Game::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => $this->mapStatus($gameData['status'] ?? 'scheduled'),
                ]
            );
        }
    }

    /**
     * Map SportsBlaze status to our status values.
     */
    protected function mapStatus(string $apiStatus): string
    {
        return match (strtolower($apiStatus)) {
            'scheduled', 'created' => 'scheduled',
            'inprogress', 'in_progress', 'live' => 'live',
            'halftime' => 'halftime',
            'complete', 'closed', 'final' => 'final',
            'postponed' => 'postponed',
            'cancelled', 'canceled' => 'cancelled',
            default => 'scheduled',
        };
    }

    /**
     * Format a game for the mobile app.
     */
    private function formatGame(Game $game): array
    {
        return [
            'id' => (string) $game->id,
            'homeTeam' => [
                'id' => (string) $game->homeTeam->id,
                'name' => $game->homeTeam->nickname,
                'abbreviation' => $game->homeTeam->abbreviation,
                'logoUrl' => $game->homeTeam->logo_url,
                'color' => $game->homeTeam->color,
            ],
            'awayTeam' => [
                'id' => (string) $game->awayTeam->id,
                'name' => $game->awayTeam->nickname,
                'abbreviation' => $game->awayTeam->abbreviation,
                'logoUrl' => $game->awayTeam->logo_url,
                'color' => $game->awayTeam->color,
            ],
            'arena' => [
                'name' => $game->homeTeam->arena_name ?? $game->homeTeam->name . ' Arena',
                'city' => $game->homeTeam->arena_city ?? $game->homeTeam->location,
                'state' => $game->homeTeam->arena_state ?? '',
                'latitude' => (float) ($game->homeTeam->arena_latitude ?? 0),
                'longitude' => (float) ($game->homeTeam->arena_longitude ?? 0),
            ],
            'scheduledAt' => $game->scheduled_at->toISOString(),
            'status' => $game->status,
        ];
    }
}
