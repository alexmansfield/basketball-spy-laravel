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
     * Fetch NBA games for a specific date using Perplexity with web search.
     * Falls back to OpenAI if Perplexity is not configured.
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

        // Try Perplexity first (has web search built-in)
        $perplexityKey = config('services.perplexity.key');
        if (!empty($perplexityKey)) {
            try {
                $games = $this->fetchFromPerplexity($date, $perplexityKey);
                if (!empty($games)) {
                    Cache::put($cacheKey, $games, self::CACHE_TTL);
                    return $games;
                }
            } catch (\Exception $e) {
                Log::warning('NBAScheduleService: Perplexity failed, trying OpenAI', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to OpenAI
        $openaiKey = config('services.openai.key');
        if (empty($openaiKey)) {
            Log::warning('NBAScheduleService: No API keys configured');
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
     * Fetch schedule from Perplexity API with built-in web search.
     * This is the preferred method as it has real-time web access.
     */
    protected function fetchFromPerplexity(string $date, string $apiKey): array
    {
        $formattedDate = Carbon::parse($date)->format('F j, Y');
        $dayOfWeek = Carbon::parse($date)->format('l');

        // Get team abbreviations from our database for the prompt
        $teamAbbrs = Team::pluck('abbreviation')->implode(', ');

        $prompt = <<<PROMPT
NBA schedule {$formattedDate}

Return ONLY a JSON array of today's NBA games:
[
  {
    "home_team": "LAL",
    "away_team": "BOS",
    "scheduled_time": "7:30 PM",
    "timezone": "PT",
    "arena": "Crypto.com Arena"
  }
]

Valid team abbreviations: {$teamAbbrs}

If no games, return: []

Include the local timezone for each game (PT, MT, CT, or ET).
PROMPT;

        Log::info('NBAScheduleService: Calling Perplexity', [
            'date' => $date,
            'formatted_date' => $formattedDate,
        ]);

        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that provides NBA game schedules in JSON format only. Search for the current NBA schedule. Never include markdown formatting or explanations - only return valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 2000,
            ]);

        if (!$response->successful()) {
            Log::error('NBAScheduleService: Perplexity API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();

        Log::info('NBAScheduleService: Perplexity response received', [
            'has_choices' => isset($data['choices']),
        ]);

        // Extract the text content from the response
        $content = $this->extractContent($data);

        Log::info('NBAScheduleService: Perplexity content', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 300),
        ]);

        if (empty($content)) {
            Log::warning('NBAScheduleService: Empty response from Perplexity');
            return [];
        }

        // Parse the JSON response
        $games = $this->parseGamesJson($content, $date);

        Log::info('NBAScheduleService: Fetched games from Perplexity', [
            'date' => $date,
            'games_count' => count($games),
        ]);

        return $games;
    }

    /**
     * Fetch schedule from OpenAI using Chat Completions API.
     * Since we can't do live web search, we rely on the model's knowledge
     * combined with known NBA schedule patterns.
     */
    protected function fetchFromOpenAI(string $date, string $apiKey): array
    {
        $formattedDate = Carbon::parse($date)->format('F j, Y');
        $dayOfWeek = Carbon::parse($date)->format('l');

        // Get team abbreviations from our database for the prompt
        $teamAbbrs = Team::pluck('abbreviation')->implode(', ');

        // Use chat completions API which is reliable
        $prompt = <<<PROMPT
NBA schedule {$formattedDate}

Return ONLY a JSON array:
[
  {
    "home_team": "LAL",
    "away_team": "BOS",
    "scheduled_time": "7:30 PM",
    "timezone": "PT",
    "arena": "Crypto.com Arena"
  }
]

Valid abbreviations: {$teamAbbrs}

If unknown or no games: []

Include local timezone (PT, MT, CT, or ET) for each game.
PROMPT;

        Log::info('NBAScheduleService: Calling OpenAI', [
            'date' => $date,
            'formatted_date' => $formattedDate,
        ]);

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that provides NBA game schedules in JSON format only. Never include markdown formatting or explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 2000,
            ]);

        if (!$response->successful()) {
            Log::error('NBAScheduleService: OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();

        Log::info('NBAScheduleService: OpenAI response received', [
            'has_choices' => isset($data['choices']),
        ]);

        // Extract the text content from the response
        $content = $this->extractContent($data);

        Log::info('NBAScheduleService: Extracted content', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 200),
        ]);

        if (empty($content)) {
            Log::warning('NBAScheduleService: Empty response from OpenAI');
            return [];
        }

        // Parse the JSON response
        $games = $this->parseGamesJson($content, $date);

        Log::info('NBAScheduleService: Fetched games from OpenAI', [
            'date' => $date,
            'games_count' => count($games),
        ]);

        return $games;
    }

    /**
     * Extract text content from OpenAI response.
     */
    protected function extractContent(array $data): string
    {
        // Chat completions format
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse the JSON games array from LLM response.
     */
    protected function parseGamesJson(string $content, string $date): array
    {
        // Clean up the response - remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $gamesData = json_decode($content, true);

        if (!is_array($gamesData)) {
            Log::warning('NBAScheduleService: Failed to parse JSON', [
                'content' => substr($content, 0, 500),
            ]);
            return [];
        }

        // Transform to our expected format
        $games = [];
        $teams = Team::all()->keyBy(fn($t) => strtoupper($t->abbreviation));

        foreach ($gamesData as $game) {
            $homeAbbr = strtoupper($game['home_team'] ?? '');
            $awayAbbr = strtoupper($game['away_team'] ?? '');

            $homeTeam = $teams->get($homeAbbr);
            $awayTeam = $teams->get($awayAbbr);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('NBAScheduleService: Unknown team abbreviation', [
                    'home' => $homeAbbr,
                    'away' => $awayAbbr,
                ]);
                continue;
            }

            // Parse scheduled time with timezone
            $timezone = $game['timezone'] ?? null;
            $scheduledAt = $this->parseScheduledTime($date, $game['scheduled_time'] ?? '7:00 PM', $timezone);

            $games[] = [
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'scheduled_at' => $scheduledAt,
                'arena' => $game['arena'] ?? $homeTeam->arena_name ?? "{$homeTeam->nickname} Arena",
                'external_id' => "llm-{$homeAbbr}-{$awayAbbr}-{$date}",
            ];
        }

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
