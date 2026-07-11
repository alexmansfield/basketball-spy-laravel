<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EspnBasketballService
{
    protected string $baseUrl = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba';

    protected int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.espn.timeout', 30);
    }

    /**
     * Fetch the 30 NBA teams (id + abbreviation) from ESPN.
     *
     * @return array<int, array{id: string, abbreviation: string, name: string}>
     */
    public function getTeams(): array
    {
        $teams = data_get($this->get("{$this->baseUrl}/teams"), 'sports.0.leagues.0.teams', []);

        return collect($teams)
            ->map(fn ($entry) => $entry['team'] ?? [])
            ->filter(fn ($team) => ! empty($team['id']) && ! empty($team['abbreviation']))
            ->map(fn ($team) => [
                'id' => (string) $team['id'],
                'abbreviation' => strtoupper($team['abbreviation']),
                'name' => $team['displayName'] ?? $team['name'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch the current roster (athletes) for an ESPN team id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoster(string $espnTeamId): array
    {
        $athletes = data_get($this->get("{$this->baseUrl}/teams/{$espnTeamId}/roster"), 'athletes', []);

        return is_array($athletes) ? $athletes : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function get(string $url): array
    {
        return Http::timeout($this->timeout)
            ->withHeaders(['User-Agent' => 'basketball-spy/1.0'])
            ->acceptJson()
            ->get($url)
            ->throw()
            ->json() ?? [];
    }
}
