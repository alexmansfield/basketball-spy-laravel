<?php

namespace App\Jobs;

use App\Models\Player;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPlayerHeadshots implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 600; // 10 minutes

    /**
     * NBA Stats API endpoint for all players.
     */
    private const NBA_STATS_URL = 'https://stats.nba.com/stats/commonallplayers';

    /**
     * NBA CDN headshot URL pattern.
     * Format: https://cdn.nba.com/headshots/nba/latest/1040x760/{player_id}.png
     */
    private const HEADSHOT_URL_PATTERN = 'https://cdn.nba.com/headshots/nba/latest/1040x760/%d.png';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SyncPlayerHeadshots: Starting headshot sync');

        // Fetch all current NBA players from NBA Stats API
        $nbaPlayers = $this->fetchNBAPlayers();

        if (empty($nbaPlayers)) {
            Log::error('SyncPlayerHeadshots: Failed to fetch NBA players');
            return;
        }

        Log::info('SyncPlayerHeadshots: Fetched NBA players', ['count' => count($nbaPlayers)]);

        // Build lookup by normalized name
        $nbaPlayerLookup = [];
        foreach ($nbaPlayers as $nbaPlayer) {
            $name = $this->normalizeName($nbaPlayer['display_name']);
            $nbaPlayerLookup[$name] = $nbaPlayer;
        }

        // Get only active players that need headshots
        $players = Player::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('nba_player_id')
                    ->orWhereNull('headshot_url');
            })
            ->get();

        Log::info('SyncPlayerHeadshots: Processing players', ['count' => $players->count()]);

        $stats = ['matched' => 0, 'unmatched' => 0, 'already_set' => 0];

        foreach ($players as $player) {
            $normalizedName = $this->normalizeName($player->name);

            if (isset($nbaPlayerLookup[$normalizedName])) {
                $nbaPlayer = $nbaPlayerLookup[$normalizedName];
                $nbaPlayerId = $nbaPlayer['person_id'];

                // Verify headshot exists
                $headshotUrl = sprintf(self::HEADSHOT_URL_PATTERN, $nbaPlayerId);

                $player->update([
                    'nba_player_id' => $nbaPlayerId,
                    'headshot_url' => $headshotUrl,
                ]);

                $stats['matched']++;
                Log::debug('SyncPlayerHeadshots: Matched player', [
                    'player' => $player->name,
                    'nba_id' => $nbaPlayerId,
                ]);
            } else {
                $stats['unmatched']++;
                Log::debug('SyncPlayerHeadshots: No match found', [
                    'player' => $player->name,
                    'normalized' => $normalizedName,
                ]);
            }
        }

        Log::info('SyncPlayerHeadshots: Sync completed', $stats);
    }

    /**
     * Fetch all current NBA players from the NBA Stats API.
     */
    protected function fetchNBAPlayers(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Referer' => 'https://www.nba.com/',
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Accept' => 'application/json',
                ])
                ->get(self::NBA_STATS_URL, [
                    'LeagueID' => '00',
                    'Season' => $this->getCurrentSeason(),
                    'IsOnlyCurrentSeason' => '1',
                ]);

            if (!$response->successful()) {
                Log::error('SyncPlayerHeadshots: NBA API request failed', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();

            // Parse the NBA Stats API response format
            // resultSets[0].rowSet contains arrays with player data
            // Headers: PERSON_ID, DISPLAY_LAST_COMMA_FIRST, DISPLAY_FIRST_LAST, ROSTERSTATUS, ...
            $resultSet = $data['resultSets'][0] ?? [];
            $headers = $resultSet['headers'] ?? [];
            $rows = $resultSet['rowSet'] ?? [];

            // Find column indices
            $personIdIdx = array_search('PERSON_ID', $headers);
            $displayNameIdx = array_search('DISPLAY_FIRST_LAST', $headers);
            $rosterStatusIdx = array_search('ROSTERSTATUS', $headers);
            $teamIdIdx = array_search('TEAM_ID', $headers);

            if ($personIdIdx === false || $displayNameIdx === false) {
                Log::error('SyncPlayerHeadshots: Unexpected API response format', [
                    'headers' => $headers,
                ]);
                return [];
            }

            $players = [];
            foreach ($rows as $row) {
                // Only include players with active roster status
                $rosterStatus = $rosterStatusIdx !== false ? $row[$rosterStatusIdx] : null;

                $players[] = [
                    'person_id' => $row[$personIdIdx],
                    'display_name' => $row[$displayNameIdx],
                    'roster_status' => $rosterStatus,
                    'team_id' => $teamIdIdx !== false ? $row[$teamIdIdx] : null,
                ];
            }

            return $players;

        } catch (\Exception $e) {
            Log::error('SyncPlayerHeadshots: Exception fetching NBA players', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Normalize a player name for matching.
     */
    protected function normalizeName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower($name);

        // Remove common suffixes
        $name = preg_replace('/\s+(jr\.?|sr\.?|ii|iii|iv)$/i', '', $name);

        // Remove periods and extra spaces
        $name = str_replace('.', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove accents/diacritics
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        return trim($name);
    }

    /**
     * Get the current NBA season string (e.g., "2024-25").
     */
    protected function getCurrentSeason(): string
    {
        $year = (int) date('Y');
        $month = (int) date('n');

        // NBA season starts in October, so if we're before October, use previous year
        if ($month < 10) {
            $year--;
        }

        $nextYear = substr((string) ($year + 1), -2);

        return "{$year}-{$nextYear}";
    }
}
