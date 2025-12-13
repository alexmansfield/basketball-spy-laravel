<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NBAScheduleService
{
    /**
     * Cache TTL for schedule data (1 hour - schedule doesn't change often)
     */
    private const CACHE_TTL = 3600;

    /**
     * Fetch NBA games for a specific date using OpenAI.
     */
    public function fetchGamesForDate(string $date): array
    {
        $cacheKey = "nba_schedule_llm:{$date}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::info('NBAScheduleService: Returning cached result', ['date' => $date]);
            return $cached;
        }

        $openaiKey = config('services.openai.key');
        if (empty($openaiKey)) {
            Log::warning('NBAScheduleService: OPENAI_API_KEY not configured');
            return [];
        }

        try {
            $games = $this->fetchFromOpenAI($date, $openaiKey);
            Cache::put($cacheKey, $games, self::CACHE_TTL);
            return $games;
        } catch (\Exception $e) {
            Log::error('NBAScheduleService: Failed to fetch schedule', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Fetch schedule from OpenAI using Chat Completions API.
     */
    protected function fetchFromOpenAI(string $date, string $apiKey): array
    {
        Log::info('NBAScheduleService: Calling OpenAI Responses API with saved prompt (background mode)');

        // Start background request - model/tools defined in saved prompt
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/responses', [
                'prompt' => [
                    'id' => 'pmpt_69389a8d44cc81938188f27bcdcf0df606e9bff2d576d7ec',
                ],
                'background' => true,
            ]);

        if (!$response->successful()) {
            Log::error('NBAScheduleService: OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();
        $responseId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        Log::info('NBAScheduleService: Background request started', [
            'response_id' => $responseId,
            'status' => $status,
        ]);

        if (!$responseId) {
            Log::error('NBAScheduleService: No response ID returned');
            return [];
        }

        // Poll for completion (max 5 minutes)
        $maxAttempts = 60;
        $pollInterval = 5; // seconds

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            sleep($pollInterval);

            $pollResponse = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])
                ->get("https://api.openai.com/v1/responses/{$responseId}");

            if (!$pollResponse->successful()) {
                Log::warning('NBAScheduleService: Poll request failed', [
                    'attempt' => $attempt,
                    'status' => $pollResponse->status(),
                ]);
                continue;
            }

            $pollData = $pollResponse->json();
            $status = $pollData['status'] ?? 'unknown';

            Log::info('NBAScheduleService: Poll status', [
                'attempt' => $attempt,
                'status' => $status,
            ]);

            if ($status === 'completed') {
                $content = $this->extractContent($pollData);

                Log::info('NBAScheduleService: Raw API response', [
                    'content_length' => strlen($content),
                    'content' => $content,
                ]);

                if (empty($content)) {
                    Log::warning('NBAScheduleService: Empty response from OpenAI');
                    return [];
                }

                return $this->parseGamesJson($content, $date);
            }

            if ($status === 'failed' || $status === 'cancelled') {
                Log::error('NBAScheduleService: Request failed', ['status' => $status]);
                return [];
            }
        }

        Log::error('NBAScheduleService: Polling timed out after 5 minutes');
        return [];
    }

    /**
     * Extract text content from OpenAI response.
     */
    protected function extractContent(array $data): string
    {
        // Responses API format - look for output_text in output array
        if (isset($data['output'])) {
            foreach ($data['output'] as $item) {
                if (($item['type'] ?? '') === 'message') {
                    foreach ($item['content'] ?? [] as $content) {
                        if (($content['type'] ?? '') === 'output_text') {
                            return $content['text'] ?? '';
                        }
                    }
                }
            }
        }

        // Fallback to chat completions format
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse games from LLM response (JSON or markdown fallback).
     */
    protected function parseGamesJson(string $content, string $date): array
    {
        $teams = Team::all();
        $teamsByAbbr = $teams->keyBy(fn($t) => strtoupper($t->abbreviation));
        $teamsByNickname = $teams->keyBy(fn($t) => strtolower($t->nickname));

        // Try JSON first
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $gamesData = json_decode($content, true);

        // Handle {"games": [...]} wrapper
        if (is_array($gamesData) && isset($gamesData['games'])) {
            $gamesData = $gamesData['games'];
        }

        if (is_array($gamesData) && !empty($gamesData)) {
            return $this->processJsonGames($gamesData, $date, $teamsByAbbr);
        }

        // Fallback: parse markdown format like "Knicks @ Magic on Dec 13 at 7:30 PM PST"
        Log::info('NBAScheduleService: Trying markdown fallback parser');
        return $this->parseMarkdownGames($content, $date, $teamsByNickname, $teamsByAbbr);
    }

    /**
     * Process JSON games array.
     */
    protected function processJsonGames(array $gamesData, string $fallbackDate, $teamsByAbbr): array
    {
        $games = [];

        foreach ($gamesData as $game) {
            $homeAbbr = strtoupper($game['home_team'] ?? $game['home'] ?? '');
            $awayAbbr = strtoupper($game['away_team'] ?? $game['away'] ?? '');

            $homeTeam = $teamsByAbbr->get($homeAbbr);
            $awayTeam = $teamsByAbbr->get($awayAbbr);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('NBAScheduleService: Unknown team', ['home' => $homeAbbr, 'away' => $awayAbbr]);
                continue;
            }

            // Use date from game object if available, otherwise fallback
            $gameDate = $game['date'] ?? $fallbackDate;
            $timeStr = $game['scheduled_time'] ?? $game['time'] ?? '7:00 PM ET';
            $scheduledAt = $this->parseScheduledTime($gameDate, $timeStr, null);

            $games[] = [
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'scheduled_at' => $scheduledAt,
                'arena' => $game['arena'] ?? $homeTeam->arena_name ?? "{$homeTeam->nickname} Arena",
                'external_id' => "llm-{$homeAbbr}-{$awayAbbr}-{$gameDate}",
            ];
        }

        return $games;
    }

    /**
     * Parse markdown format: "Knicks @ Magic on Dec 13, 2025 at 02:30 PM PST"
     */
    protected function parseMarkdownGames(string $content, string $date, $teamsByNickname, $teamsByAbbr): array
    {
        $games = [];

        // Match patterns like "Knicks @ Magic" or "NYK @ ORL" with time
        preg_match_all('/(\w+)\s*@\s*(\w+)[^0-9]*(\d{1,2}:\d{2}\s*(?:AM|PM)\s*(?:PT|PST|PDT|ET|EST|EDT|CT|CST|CDT|MT|MST|MDT)?)/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $awayName = strtolower($match[1]);
            $homeName = strtolower($match[2]);
            $timeStr = $match[3];

            // Try nickname first, then abbreviation
            $awayTeam = $teamsByNickname->get($awayName) ?? $teamsByAbbr->get(strtoupper($match[1]));
            $homeTeam = $teamsByNickname->get($homeName) ?? $teamsByAbbr->get(strtoupper($match[2]));

            if (!$homeTeam || !$awayTeam) {
                Log::debug('NBAScheduleService: Markdown parse - unknown team', ['away' => $awayName, 'home' => $homeName]);
                continue;
            }

            $homeAbbr = $homeTeam->abbreviation;
            $awayAbbr = $awayTeam->abbreviation;
            $scheduledAt = $this->parseScheduledTime($date, $timeStr, null);

            $games[] = [
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'scheduled_at' => $scheduledAt,
                'arena' => $homeTeam->arena_name ?? "{$homeTeam->nickname} Arena",
                'external_id' => "llm-{$homeAbbr}-{$awayAbbr}-{$date}",
            ];
        }

        Log::info('NBAScheduleService: Markdown parser found games', ['count' => count($games)]);
        return $games;
    }

    /**
     * Parse a time string like "7:30 PM" with timezone into a Carbon datetime.
     */
    protected function parseScheduledTime(string $date, string $timeStr, ?string $timezone = null): Carbon
    {
        // Map timezone abbreviations to PHP timezone names
        $tzMap = [
            'PT' => 'America/Los_Angeles',
            'PST' => 'America/Los_Angeles',
            'PDT' => 'America/Los_Angeles',
            'MT' => 'America/Denver',
            'MST' => 'America/Denver',
            'MDT' => 'America/Denver',
            'CT' => 'America/Chicago',
            'CST' => 'America/Chicago',
            'CDT' => 'America/Chicago',
            'ET' => 'America/New_York',
            'EST' => 'America/New_York',
            'EDT' => 'America/New_York',
        ];

        // Extract timezone from time string if present
        if (preg_match('/\s*(ET|EST|EDT|PT|PST|PDT|CT|CST|CDT|MT|MST|MDT)\s*$/i', $timeStr, $matches)) {
            $timezone = strtoupper($matches[1]);
            $timeStr = preg_replace('/\s*(ET|EST|EDT|PT|PST|PDT|CT|CST|CDT|MT|MST|MDT)\s*$/i', '', $timeStr);
        }

        $timeStr = trim($timeStr);
        $phpTimezone = $tzMap[$timezone ?? 'ET'] ?? 'America/New_York';

        try {
            // Parse time in the local timezone, then convert to UTC for storage
            $datetime = Carbon::parse("{$date} {$timeStr}", $phpTimezone);
            return $datetime->utc();
        } catch (\Exception $e) {
            // Default to 7 PM ET if parsing fails
            return Carbon::parse("{$date} 19:00:00", 'America/New_York')->utc();
        }
    }

    /**
     * Store games from LLM response into the database.
     */
    public function storeGames(array $games): int
    {
        $stored = 0;

        foreach ($games as $gameData) {
            Game::updateOrCreate(
                ['external_id' => $gameData['external_id']],
                [
                    'home_team_id' => $gameData['home_team_id'],
                    'away_team_id' => $gameData['away_team_id'],
                    'scheduled_at' => $gameData['scheduled_at'],
                    'status' => 'scheduled',
                ]
            );
            $stored++;
        }

        return $stored;
    }
}
