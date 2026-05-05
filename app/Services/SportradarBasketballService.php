<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SportradarBasketballService
{
    public const PROVIDER = 'sportradar';

    public const LEAGUE_NCAAMB = 'ncaamb';

    public const LEAGUE_GLEAGUE = 'gleague';

    protected string $apiKey;

    protected string $accessLevel;

    protected string $language;

    protected int $timeout;

    protected int $maxRetries;

    protected int $retrySleepMs;

    protected array $leagues = [
        self::LEAGUE_NCAAMB => [
            'display' => 'NCAAB',
            'host' => 'https://api.sportradar.com',
            'path' => 'ncaamb',
        ],
        self::LEAGUE_GLEAGUE => [
            'display' => 'G League',
            'host' => 'https://api.sportradar.us',
            'path' => 'nbdl',
        ],
    ];

    public function __construct()
    {
        $this->apiKey = (string) config('services.sportradar.key', '');
        $this->accessLevel = (string) config('services.sportradar.access_level', 'trial');
        $this->language = (string) config('services.sportradar.language', 'en');
        $this->timeout = (int) config('services.sportradar.timeout', 30);
        $this->maxRetries = max(1, (int) config('services.sportradar.max_retries', 4));
        $this->retrySleepMs = max(250, (int) config('services.sportradar.retry_sleep_ms', 1200));
    }

    public function configured(): bool
    {
        return $this->apiKey !== '';
    }

    public function normalizeLeague(string $league): string
    {
        $normalized = Str::of($league)->lower()->replace(['-', '_', ' '], '')->toString();

        return match ($normalized) {
            'ncaamb', 'ncaam', 'ncaa', 'collegebasketball' => self::LEAGUE_NCAAMB,
            'gleague', 'gleag', 'g', 'nbdl', 'nbagleague' => self::LEAGUE_GLEAGUE,
            default => throw new RuntimeException("Unsupported Sportradar league [{$league}]."),
        };
    }

    public function displayLeague(string $league): string
    {
        $league = $this->normalizeLeague($league);

        return $this->leagues[$league]['display'];
    }

    public function supportedLeagues(): array
    {
        return array_keys($this->leagues);
    }

    public function getLeagueHierarchy(string $league): array
    {
        return $this->request($league, 'league/hierarchy.json');
    }

    public function getTeams(string $league): array
    {
        $hierarchy = $this->getLeagueHierarchy($league);
        $teams = [];

        $this->collectTeams($hierarchy, [], $teams);

        return array_values($teams);
    }

    public function getTeamProfile(string $league, string $teamId): array
    {
        return $this->request($league, "teams/{$teamId}/profile.json");
    }

    public function extractPlayersFromProfile(array $profile): array
    {
        foreach (['players', 'roster'] as $key) {
            if (isset($profile[$key]) && is_array($profile[$key])) {
                return $profile[$key];
            }
        }

        if (isset($profile['team']['players']) && is_array($profile['team']['players'])) {
            return $profile['team']['players'];
        }

        return [];
    }

    public function request(string $league, string $endpoint, array $params = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('SPORTRADAR_API_KEY is not configured.');
        }

        $url = $this->url($league, $endpoint);

        $response = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true) || $attempt === $this->maxRetries) {
                break;
            }

            $sleepMs = $this->retryDelayMs($response->header('Retry-After'), $attempt);

            Log::warning('Sportradar basketball request retrying', [
                'league' => $this->normalizeLeague($league),
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'attempt' => $attempt,
                'sleep_ms' => $sleepMs,
            ]);

            usleep($sleepMs * 1000);
        }

        Log::error('Sportradar basketball request failed', [
            'league' => $this->normalizeLeague($league),
            'endpoint' => $endpoint,
            'status' => $response?->status(),
            'body' => Str::limit($response?->body() ?? '', 500),
        ]);

        throw new RuntimeException("Sportradar request failed with HTTP {$response?->status()} for {$endpoint}.");
    }

    protected function url(string $league, string $endpoint): string
    {
        $league = $this->normalizeLeague($league);
        $config = $this->leagues[$league];
        $endpoint = ltrim($endpoint, '/');

        return "{$config['host']}/{$config['path']}/{$this->accessLevel}/v8/{$this->language}/{$endpoint}";
    }

    protected function retryDelayMs(?string $retryAfter, int $attempt): int
    {
        if (is_numeric($retryAfter)) {
            return max(1, (int) $retryAfter) * 1000;
        }

        return $this->retrySleepMs * $attempt;
    }

    protected function collectTeams(array $node, array $context, array &$teams): void
    {
        foreach (($node['teams'] ?? []) as $team) {
            if (! is_array($team) || empty($team['id'])) {
                continue;
            }

            $teams[$team['id']] = array_replace($team, [
                '_context' => $context,
            ]);
        }

        foreach (($node['conferences'] ?? []) as $conference) {
            if (! is_array($conference)) {
                continue;
            }

            $this->collectTeams($conference, array_replace($context, [
                'conference_id' => $conference['id'] ?? null,
                'conference_name' => $conference['name'] ?? null,
                'conference_alias' => $conference['alias'] ?? null,
            ]), $teams);
        }

        foreach (($node['divisions'] ?? []) as $division) {
            if (! is_array($division)) {
                continue;
            }

            $this->collectTeams($division, array_replace($context, [
                'division_id' => $division['id'] ?? null,
                'division_name' => $division['name'] ?? null,
                'division_alias' => $division['alias'] ?? null,
            ]), $teams);
        }
    }
}
