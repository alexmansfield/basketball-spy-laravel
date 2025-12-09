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
     * Fetch NBA games for a specific date using OpenAI with web search.
     * Uses gpt-4o-mini for cost efficiency.
     */
    public function fetchGamesForDate(string $date): array
    {
        $cacheKey = "nba_schedule_llm:{$date}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $apiKey = config('services.openai.key');
        if (empty($apiKey)) {
            Log::warning('NBAScheduleService: OPENAI_API_KEY not configured');
            return [];
        }

        try {
            $games = $this->fetchFromOpenAI($date, $apiKey);

            // Cache the result
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
You are an NBA schedule assistant. Based on your knowledge of the 2024-2025 NBA season schedule, provide the NBA games scheduled for {$formattedDate} ({$dayOfWeek}).

Return ONLY a valid JSON array of games with this exact structure (no markdown, no explanation, no other text):
[
  {
    "home_team": "LAL",
    "away_team": "BOS",
    "scheduled_time": "7:30 PM ET",
    "arena": "Crypto.com Arena"
  }
]

Use these exact team abbreviations: {$teamAbbrs}

If you don't know the exact schedule for this date, return an empty array: []

Rules:
- Use standard 3-letter NBA team abbreviations from the list above
- Include ALL games you know are scheduled for this date
- Times should be in Eastern Time (ET)
- Return ONLY the JSON array, nothing else - no explanations
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

            // Parse scheduled time
            $scheduledAt = $this->parseScheduledTime($date, $game['scheduled_time'] ?? '7:00 PM ET');

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
     * Parse a time string like "7:30 PM ET" into a Carbon datetime.
     */
    protected function parseScheduledTime(string $date, string $timeStr): Carbon
    {
        // Remove timezone indicator
        $timeStr = preg_replace('/\s*(ET|EST|EDT|PT|PST|PDT|CT|CST|CDT|MT|MST|MDT)\s*$/i', '', $timeStr);
        $timeStr = trim($timeStr);

        try {
            // Parse time in Eastern timezone, then convert to UTC for storage
            $datetime = Carbon::parse("{$date} {$timeStr}", 'America/New_York');
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
