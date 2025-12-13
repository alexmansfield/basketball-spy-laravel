<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use App\Jobs\SyncGames;
use App\Services\NBAScheduleService;
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
     * Get today's games (legacy endpoint - redirects to upcoming).
     *
     * GET /api/games/today
     */
    public function today(Request $request): JsonResponse
    {
        return $this->upcoming($request);
    }

    /**
     * Get upcoming games.
     *
     * GET /api/games/upcoming
     *
     * Returns the next batch of games that haven't finished yet,
     * regardless of date. Sorted by scheduled time.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $nowET = now('America/New_York');
        $todayET = $nowET->toDateString();

        // Get upcoming games (not finished, from now onwards)
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->upcoming()
            ->limit(15)
            ->get();

        Log::info('GamesController: Upcoming games query', [
            'today_et' => $todayET,
            'upcoming_count' => $games->count(),
        ]);

        // If no upcoming games, the sync may not have run yet
        if ($games->isEmpty()) {
            Log::warning('GamesController: No upcoming games found in DB');
        }

        // Determine what we're showing based on the first game's date
        $showing = 'upcoming';
        $date = $todayET;

        if ($games->isNotEmpty()) {
            $firstGameDate = $games->first()->scheduled_at->setTimezone('America/New_York')->toDateString();
            $date = $firstGameDate;

            if ($firstGameDate === $todayET) {
                $showing = 'today';
            } elseif ($firstGameDate === $nowET->copy()->addDay()->toDateString()) {
                $showing = 'tomorrow';
            }
        }

        return response()->json([
            'games' => $games->map(fn($game) => $this->formatGame($game))->toArray(),
            'date' => $date,
            'showing' => $showing,
        ]);
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

            // If no games in DB, try fetching from external sources
            if ($games->isEmpty()) {
                $games = $this->fetchGamesFromApi($date);

                // If SportsBlaze failed or returned no games, try LLM-based fetcher
                if ($games->isEmpty()) {
                    $games = $this->fetchGamesFromLLM($date);
                }
            }

            return [
                'games' => $games->map(function ($game) {
                    return $this->formatGame($game);
                })->toArray(),
                'date' => $date,
                'debug' => [
                    'total_games_in_db' => Game::count(),
                    'games_for_date' => $games->count(),
                    'api_error' => request()->attributes->get('api_error'),
                    'api_response' => request()->attributes->get('api_response'),
                    'team_match_error' => request()->attributes->get('team_match_error'),
                    'llm_response' => request()->attributes->get('llm_response'),
                    'llm_error' => request()->attributes->get('llm_error'),
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
            // Store the error for debug output
            request()->attributes->set('api_error', 'SPORTSBLAZE_API_KEY not configured');
            return Game::query()->where('id', 0)->get(); // Empty collection
        }

        try {
            // SportsBlaze schedule endpoint: /nba/v1/schedule/daily/{date}.json
            $url = "https://api.sportsblaze.com/nba/v1/schedule/daily/{$date}.json";
            Log::info('GamesController: Fetching from SportsBlaze', ['url' => $url]);

            $response = Http::timeout(10)->get($url, ['key' => $apiKey]);

            if (!$response->successful()) {
                Log::error('GamesController: SportsBlaze API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'date' => $date,
                ]);
                request()->attributes->set('api_error', "API returned status {$response->status()}: {$response->body()}");
                return Game::query()->where('id', 0)->get();
            }

            $data = $response->json();
            Log::info('GamesController: SportsBlaze response', [
                'games_count' => count($data['games'] ?? []),
            ]);

            request()->attributes->set('api_response', [
                'games_in_response' => count($data['games'] ?? []),
            ]);

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
            request()->attributes->set('api_error', "Exception: {$e->getMessage()}");
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

        // Log available teams for debugging
        Log::info('GamesController: Available teams', [
            'team_abbrs' => $teams->keys()->toArray(),
        ]);

        foreach ($games as $gameData) {
            $externalId = $gameData['id'] ?? null;
            if (!$externalId) {
                continue;
            }

            // Log the raw game data structure for debugging
            Log::info('GamesController: Raw game data', [
                'game' => $gameData,
            ]);

            // Find teams by abbreviation - check multiple possible field names
            $homeAbbr = strtolower($gameData['home']['alias'] ?? $gameData['home']['abbreviation'] ?? $gameData['home_team'] ?? '');
            $awayAbbr = strtolower($gameData['away']['alias'] ?? $gameData['away']['abbreviation'] ?? $gameData['away_team'] ?? '');

            $homeTeam = $teams->get($homeAbbr);
            $awayTeam = $teams->get($awayAbbr);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('GamesController: Could not match teams', [
                    'home_abbr' => $homeAbbr,
                    'away_abbr' => $awayAbbr,
                    'available_teams' => $teams->keys()->take(10)->toArray(),
                ]);

                // Store for debug output
                request()->attributes->set('team_match_error', [
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
     * Fetch games using LLM (fallback when SportsBlaze fails).
     */
    protected function fetchGamesFromLLM(string $date): \Illuminate\Database\Eloquent\Collection
    {
        // Check if OpenAI key is configured
        $openaiKey = config('services.openai.key');
        if (empty($openaiKey)) {
            request()->attributes->set('llm_error', 'OPENAI_API_KEY not configured');
            Log::warning('GamesController: OPENAI_API_KEY not configured');
            return Game::query()->where('id', 0)->get();
        }

        $service = app(NBAScheduleService::class);

        try {
            Log::info('GamesController: Attempting LLM fetch', ['date' => $date]);
            $gamesData = $service->fetchGamesForDate($date);

            if (empty($gamesData)) {
                request()->attributes->set('llm_response', [
                    'games_found' => 0,
                    'message' => 'LLM returned no games for this date',
                ]);
                return Game::query()->where('id', 0)->get();
            }

            // Store the games
            $stored = $service->storeGames($gamesData);

            request()->attributes->set('llm_response', [
                'games_found' => count($gamesData),
                'games_stored' => $stored,
            ]);

            // Re-fetch from database with relationships
            return Game::with(['homeTeam', 'awayTeam'])
                ->forDate($date)
                ->orderBy('scheduled_at')
                ->get();

        } catch (\Exception $e) {
            Log::error('GamesController: LLM fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'date' => $date,
            ]);
            request()->attributes->set('llm_error', $e->getMessage());
            return Game::query()->where('id', 0)->get();
        }
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
            'scheduleDate' => $game->scheduled_at->toDateString(),
            'status' => $game->status,
        ];
    }
}
