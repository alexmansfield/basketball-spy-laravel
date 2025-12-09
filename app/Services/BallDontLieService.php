<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BallDontLieService
{
    /**
     * Rate limit: 5 requests per minute for free tier.
     */
    private const RATE_LIMIT = 5;
    private const RATE_LIMIT_WINDOW = 60; // seconds

    /**
     * Cache key for tracking API calls.
     */
    private const RATE_LIMIT_CACHE_KEY = 'balldontlie_rate_limit';

    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.balldontlie.base_url', 'https://api.balldontlie.io/v1');
        $this->apiKey = config('services.balldontlie.key', '');
    }

    /**
     * Make a rate-limited API request.
     */
    public function request(string $endpoint, array $params = []): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('BallDontLie: API key not configured');
            return null;
        }

        // Check rate limit
        $this->waitForRateLimit();

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                ])
                ->get($url, $params);

            // Track this request for rate limiting
            $this->trackRequest();

            if (!$response->successful()) {
                Log::error('BallDontLie: API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Handle rate limit exceeded (429)
                if ($response->status() === 429) {
                    Log::warning('BallDontLie: Rate limit exceeded, waiting 60 seconds');
                    sleep(60);
                    return $this->request($endpoint, $params); // Retry
                }

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BallDontLie: Request exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Wait if we've hit the rate limit.
     */
    protected function waitForRateLimit(): void
    {
        $timestamps = Cache::get(self::RATE_LIMIT_CACHE_KEY, []);
        $now = time();

        // Remove timestamps older than the rate limit window
        $timestamps = array_filter($timestamps, fn($ts) => $ts > ($now - self::RATE_LIMIT_WINDOW));

        // If we're at the rate limit, wait
        if (count($timestamps) >= self::RATE_LIMIT) {
            $oldestTimestamp = min($timestamps);
            $waitTime = self::RATE_LIMIT_WINDOW - ($now - $oldestTimestamp) + 1;

            if ($waitTime > 0) {
                Log::info("BallDontLie: Rate limit reached, waiting {$waitTime} seconds");
                sleep($waitTime);
            }
        }
    }

    /**
     * Track a request for rate limiting.
     */
    protected function trackRequest(): void
    {
        $timestamps = Cache::get(self::RATE_LIMIT_CACHE_KEY, []);
        $now = time();

        // Remove old timestamps
        $timestamps = array_filter($timestamps, fn($ts) => $ts > ($now - self::RATE_LIMIT_WINDOW));

        // Add current timestamp
        $timestamps[] = $now;

        Cache::put(self::RATE_LIMIT_CACHE_KEY, $timestamps, self::RATE_LIMIT_WINDOW);
    }

    /**
     * Get all NBA teams.
     */
    public function getTeams(): array
    {
        $response = $this->request('teams');
        return $response['data'] ?? [];
    }

    /**
     * Get players with pagination.
     *
     * @param int|null $cursor Pagination cursor
     * @param int $perPage Results per page (max 100)
     * @param array $teamIds Filter by team IDs
     */
    public function getPlayers(?int $cursor = null, int $perPage = 100, array $teamIds = []): array
    {
        $params = ['per_page' => min($perPage, 100)];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        if (!empty($teamIds)) {
            $params['team_ids'] = $teamIds;
        }

        return $this->request('players', $params) ?? ['data' => [], 'meta' => []];
    }

    /**
     * Get all players (handles pagination automatically).
     */
    public function getAllPlayers(): \Generator
    {
        $cursor = null;

        do {
            $response = $this->getPlayers($cursor);
            $players = $response['data'] ?? [];

            foreach ($players as $player) {
                yield $player;
            }

            $cursor = $response['meta']['next_cursor'] ?? null;
        } while ($cursor !== null);
    }

    /**
     * Get active players (no pagination - returns all at once).
     */
    public function getActivePlayers(): array
    {
        $response = $this->request('players/active');
        return $response['data'] ?? [];
    }

    /**
     * Get games with date filtering.
     *
     * @param array $dates Array of dates in YYYY-MM-DD format
     * @param int|null $cursor Pagination cursor
     * @param int $perPage Results per page (max 100)
     */
    public function getGames(array $dates = [], ?int $cursor = null, int $perPage = 100): array
    {
        $params = ['per_page' => min($perPage, 100)];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        if (!empty($dates)) {
            $params['dates'] = $dates;
        }

        return $this->request('games', $params) ?? ['data' => [], 'meta' => []];
    }

    /**
     * Get games by date range.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     */
    public function getGamesByDateRange(string $startDate, string $endDate, ?int $cursor = null, int $perPage = 100): array
    {
        $params = [
            'per_page' => min($perPage, 100),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        return $this->request('games', $params) ?? ['data' => [], 'meta' => []];
    }

    /**
     * Get all games for a date range (handles pagination automatically).
     */
    public function getAllGamesForDateRange(string $startDate, string $endDate): \Generator
    {
        $cursor = null;

        do {
            $response = $this->getGamesByDateRange($startDate, $endDate, $cursor);
            $games = $response['data'] ?? [];

            foreach ($games as $game) {
                yield $game;
            }

            $cursor = $response['meta']['next_cursor'] ?? null;
        } while ($cursor !== null);
    }
}
