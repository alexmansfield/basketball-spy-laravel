<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncNBASchedule implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 300; // 5 minutes to handle 70-80 second API calls with buffer

    /**
     * OpenAI Responses API prompt ID for NBA schedule.
     */
    private const PROMPT_ID = 'pmpt_69389a8d44cc81938188f27bcdcf0df606e9bff2d576d7ec';

    /**
     * Number of days to fetch.
     */
    protected int $days;

    /**
     * Create a new job instance.
     */
    public function __construct(int $days = 7)
    {
        $this->days = $days;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            Log::error('SyncNBASchedule: OPENAI_API_KEY is not configured');
            return;
        }

        try {
            // Build the date range for the prompt
            $startDate = now();
            $endDate = now()->addDays($this->days - 1);

            $dateRange = $startDate->format('F j, Y') . ' to ' . $endDate->format('F j, Y');
            $dates = [];
            for ($i = 0; $i < $this->days; $i++) {
                $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
            }

            Log::info('SyncNBASchedule: Fetching schedule', [
                'date_range' => $dateRange,
                'days' => $this->days,
            ]);

            // Call OpenAI Responses API with the prompt
            // Extended timeout for web search which can take 70-80 seconds
            $response = Http::timeout(180)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/responses', [
                    'model' => 'gpt-4o-mini',
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => "Get the NBA schedule from {$dateRange}. Today is " . now()->format('F j, Y') . ".",
                        ]
                    ],
                    'instructions' => self::PROMPT_ID,
                    'tools' => [
                        ['type' => 'web_search_preview']
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('SyncNBASchedule: OpenAI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $data = $response->json();
            $content = $this->extractContent($data);

            if (empty($content)) {
                Log::warning('SyncNBASchedule: Empty response from OpenAI');
                return;
            }

            Log::info('SyncNBASchedule: Received response', [
                'content_length' => strlen($content),
                'content_preview' => substr($content, 0, 500),
            ]);

            // Parse and store the games
            $games = $this->parseGamesJson($content);
            $stored = $this->storeGames($games);

            // Clear cache for affected dates
            foreach ($dates as $date) {
                Cache::forget("games:date:{$date}");
                Cache::forget("nba_schedule_llm:{$date}");
            }

            Log::info('SyncNBASchedule: Sync completed', [
                'games_parsed' => count($games),
                'games_stored' => $stored['created'],
                'games_updated' => $stored['updated'],
                'games_skipped' => $stored['skipped'],
            ]);

        } catch (\Exception $e) {
            Log::error('SyncNBASchedule: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract text content from OpenAI Responses API format.
     */
    protected function extractContent(array $data): string
    {
        // Handle responses API format
        if (isset($data['output'])) {
            foreach ($data['output'] as $item) {
                if ($item['type'] === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $contentItem) {
                        if ($contentItem['type'] === 'output_text') {
                            return $contentItem['text'] ?? '';
                        }
                    }
                }
            }
        }

        // Fallback for chat completions format
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse the JSON games array from the response.
     */
    protected function parseGamesJson(string $content): array
    {
        // Clean up the response - remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        // Try to extract JSON array from the content
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $content = $matches[0];
        }

        $gamesData = json_decode($content, true);

        if (!is_array($gamesData)) {
            Log::warning('SyncNBASchedule: Failed to parse JSON', [
                'content' => substr($content, 0, 1000),
            ]);
            return [];
        }

        return $gamesData;
    }

    /**
     * Store games with deduplication.
     */
    protected function storeGames(array $gamesData): array
    {
        $teams = Team::all()->keyBy(fn($t) => strtoupper($t->abbreviation));
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($gamesData as $game) {
            $homeAbbr = strtoupper($game['home_team'] ?? '');
            $awayAbbr = strtoupper($game['away_team'] ?? '');
            $gameDate = $game['date'] ?? $game['game_date'] ?? null;

            if (empty($gameDate)) {
                Log::warning('SyncNBASchedule: Game missing date', ['game' => $game]);
                $stats['skipped']++;
                continue;
            }

            $homeTeam = $teams->get($homeAbbr);
            $awayTeam = $teams->get($awayAbbr);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('SyncNBASchedule: Unknown team abbreviation', [
                    'home' => $homeAbbr,
                    'away' => $awayAbbr,
                ]);
                $stats['skipped']++;
                continue;
            }

            // Parse scheduled time
            $scheduledAt = $this->parseScheduledTime(
                $gameDate,
                $game['scheduled_time'] ?? $game['time'] ?? '7:00 PM',
                $game['timezone'] ?? null
            );

            // Generate unique external ID for deduplication
            // Format: date-homeTeam-awayTeam (e.g., 2025-12-09-LAL-BOS)
            $externalId = "{$gameDate}-{$homeAbbr}-{$awayAbbr}";

            // Check if game already exists
            $existingGame = Game::where('external_id', $externalId)->first();

            if ($existingGame) {
                // Update existing game
                $existingGame->update([
                    'scheduled_at' => $scheduledAt,
                    'status' => $game['status'] ?? 'scheduled',
                ]);
                $stats['updated']++;
            } else {
                // Create new game
                Game::create([
                    'external_id' => $externalId,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => $game['status'] ?? 'scheduled',
                ]);
                $stats['created']++;
            }
        }

        return $stats;
    }

    /**
     * Parse a time string with timezone into a Carbon datetime.
     */
    protected function parseScheduledTime(string $date, string $timeStr, ?string $timezone = null): Carbon
    {
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
            $datetime = Carbon::parse("{$date} {$timeStr}", $phpTimezone);
            return $datetime->utc();
        } catch (\Exception $e) {
            return Carbon::parse("{$date} 19:00:00", 'America/New_York')->utc();
        }
    }
}
