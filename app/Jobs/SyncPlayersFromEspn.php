<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Team;
use App\Services\EspnBasketballService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncPlayersFromEspn implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 300;

    /**
     * Reconcile NBA rosters from ESPN's public API.
     *
     * Identity is resolved by espn_id, then normalized name + birthdate, then
     * normalized name -- so existing rows (from the seed or prior providers)
     * link to their ESPN record instead of duplicating, and every subsequent
     * run matches on the stored espn_id. Custom, scout-added players (no
     * provider id) are never matched, overwritten, or deactivated, and rows
     * are only ever deactivated -- never deleted -- so reports are preserved.
     */
    public function handle(EspnBasketballService $espn): void
    {
        Log::info('SyncPlayersFromEspn: Starting NBA roster sync');

        $teamsByAbbr = Team::where('league', 'NBA')->get()->keyBy(fn (Team $team) => strtoupper($team->abbreviation));

        if ($teamsByAbbr->isEmpty()) {
            Log::warning('SyncPlayersFromEspn: No NBA teams found.');

            return;
        }

        $existing = Player::whereNotNull('espn_id')
            ->orWhereNotNull('balldontlie_id')
            ->orWhereNotNull('nba_player_id')
            ->get();

        $byEspnId = $existing->whereNotNull('espn_id')->keyBy('espn_id');
        $byNameDob = $this->indexByNameAndDob($existing);
        $byName = $this->indexByName($existing);

        $nbaTeamIds = $teamsByAbbr->pluck('id');
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'deactivated' => 0];
        $seenIds = [];

        foreach ($espn->getTeams() as $espnTeam) {
            $team = $teamsByAbbr->get($espnTeam['abbreviation']);

            if (! $team) {
                Log::warning('SyncPlayersFromEspn: No local team for ESPN abbreviation', ['abbr' => $espnTeam['abbreviation']]);

                continue;
            }

            try {
                $roster = $espn->getRoster($espnTeam['id']);
            } catch (Throwable $e) {
                Log::error('SyncPlayersFromEspn: Failed to fetch roster', ['team' => $espnTeam['abbreviation'], 'error' => $e->getMessage()]);

                continue;
            }

            foreach ($roster as $athlete) {
                $espnId = isset($athlete['id']) ? (int) $athlete['id'] : null;
                $name = trim((string) ($athlete['fullName'] ?? $athlete['displayName'] ?? ''));

                if (! $espnId || $name === '') {
                    $stats['skipped']++;

                    continue;
                }

                $birthdate = $this->parseBirthdate($athlete['dateOfBirth'] ?? null);

                $player = $byEspnId->get($espnId)
                    ?? ($birthdate ? $byNameDob->get($this->normalizeName($name).'|'.$birthdate) : null)
                    ?? $byName->get($this->normalizeName($name));

                $attributes = [
                    'espn_id' => $espnId,
                    'team_id' => $team->id,
                    'name' => $name,
                    'jersey' => (string) ($athlete['jersey'] ?? ''),
                    'position' => (string) data_get($athlete, 'position.abbreviation', ''),
                    'height' => $this->formatHeight($athlete['displayHeight'] ?? null),
                    'weight' => $this->formatWeight($athlete['displayWeight'] ?? null),
                    'birthdate' => $birthdate ?? $player?->birthdate,
                    'headshot_url' => data_get($athlete, 'headshot.href') ?: $player?->headshot_url,
                    'is_active' => true,
                    'extra_attributes' => array_replace((array) ($player?->extra_attributes ?? []), [
                        'espn' => [
                            'id' => $espnId,
                            'college' => data_get($athlete, 'college.name'),
                            'experience' => data_get($athlete, 'experience.years'),
                        ],
                    ]),
                ];

                if ($player) {
                    $player->fill($attributes)->save();
                    $seenIds[] = $player->id;
                    $stats['updated']++;
                } else {
                    $seenIds[] = Player::create($attributes)->id;
                    $stats['created']++;
                }
            }
        }

        // Deactivate provider-managed NBA players no longer on any ESPN roster.
        // Guarded on a non-empty seen set, and never touches custom players.
        if ($seenIds !== []) {
            $stats['deactivated'] = Player::whereIn('team_id', $nbaTeamIds)
                ->whereNotIn('id', $seenIds)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNotNull('espn_id')
                        ->orWhereNotNull('balldontlie_id')
                        ->orWhereNotNull('nba_player_id');
                })
                ->update(['is_active' => false]);
        }

        Log::info('SyncPlayersFromEspn: Sync completed', $stats);
    }

    /**
     * @param  Collection<int, Player>  $players
     * @return Collection<string, Player>
     */
    protected function indexByNameAndDob(Collection $players): Collection
    {
        return $players->reduce(function (Collection $carry, Player $player) {
            if (! $player->birthdate) {
                return $carry;
            }

            $key = $this->normalizeName($player->name).'|'.$player->birthdate->format('Y-m-d');

            if (! $carry->has($key)) {
                $carry->put($key, $player);
            }

            return $carry;
        }, collect());
    }

    /**
     * @param  Collection<int, Player>  $players
     * @return Collection<string, Player>
     */
    protected function indexByName(Collection $players): Collection
    {
        return $players->reduce(function (Collection $carry, Player $player) {
            $key = $this->normalizeName($player->name);

            if ($key !== '' && ! $carry->has($key)) {
                $carry->put($key, $player);
            }

            return $carry;
        }, collect());
    }

    protected function normalizeName(string $name): string
    {
        $name = Str::of($name)->ascii()->lower()->toString();
        $name = preg_replace('/\b(jr|sr|ii|iii|iv|v)\b/', '', $name);
        $name = preg_replace('/[^a-z0-9 ]/', '', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    protected function parseBirthdate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Normalize ESPN height like "6' 6\"" to "6'6\"".
     */
    protected function formatHeight(?string $height): ?string
    {
        if (! $height) {
            return null;
        }

        return str_replace(' ', '', $height);
    }

    protected function formatWeight(?string $weight): ?string
    {
        return $weight ? trim($weight) : null;
    }
}
