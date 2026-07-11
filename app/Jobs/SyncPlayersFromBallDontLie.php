<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Team;
use App\Services\BallDontLieService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncPlayersFromBallDontLie implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 300;

    /**
     * Execute the job.
     *
     * Reconciles active NBA rosters from BallDontLie. Identity is matched by
     * balldontlie_id, then by normalized name -- NOT by nba_player_id, because
     * BallDontLie uses its own id namespace (its id is NOT the NBA player id).
     * Matched rows keep their real nba_player_id and headshot_url so headshots
     * (which are built from the NBA id) never break.
     */
    public function handle(BallDontLieService $api): void
    {
        Log::info('SyncPlayersFromBallDontLie: Starting active player sync');

        $teamsByBdlId = Team::whereNotNull('balldontlie_id')->get()->keyBy('balldontlie_id');

        if ($teamsByBdlId->isEmpty()) {
            Log::warning('SyncPlayersFromBallDontLie: No teams with balldontlie_id found. Run app:sync-teams first.');

            return;
        }

        $activePlayers = $api->getAllActivePlayers();

        if (empty($activePlayers)) {
            Log::error('SyncPlayersFromBallDontLie: No active players returned. Check API subscription tier.');

            return;
        }

        $nbaTeamIds = $teamsByBdlId->pluck('id');

        // Snapshot rows we may match against. Only sync-managed players -- those
        // carrying a balldontlie_id (synced) or nba_player_id (seeded) -- are
        // candidates. Custom players added by scouts (neither id set) are never
        // matched, so the sync can never overwrite user-created data.
        $existing = Player::where(function ($query) use ($nbaTeamIds) {
            $query->whereIn('team_id', $nbaTeamIds)
                ->orWhereNotNull('balldontlie_id');
        })->get()->filter(fn (Player $player) => $this->isSyncManaged($player));

        $byBdlId = $existing->whereNotNull('balldontlie_id')->keyBy('balldontlie_id');
        $byName = $this->indexByNormalizedName($existing);

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'deactivated' => 0];
        $seenIds = [];

        foreach ($activePlayers as $playerData) {
            $bdlId = $playerData['id'] ?? null;
            $team = $teamsByBdlId->get($playerData['team']['id'] ?? null);

            if (! $bdlId || ! $team) {
                $stats['skipped']++;

                continue;
            }

            $name = trim(($playerData['first_name'] ?? '').' '.($playerData['last_name'] ?? ''));
            $player = $byBdlId->get($bdlId) ?? $byName->get($this->normalizeName($name));

            // Fields BallDontLie is authoritative for. Deliberately excludes
            // nba_player_id and headshot_url so a matched row keeps its real id.
            $attributes = [
                'balldontlie_id' => $bdlId,
                'team_id' => $team->id,
                'name' => $name,
                'jersey' => $playerData['jersey_number'] ?? '',
                'position' => $playerData['position'] ?? '',
                'height' => $this->formatHeight($playerData['height'] ?? null),
                'weight' => isset($playerData['weight']) ? $playerData['weight'].' lbs' : null,
                'is_active' => true,
                'extra_attributes' => [
                    'first_name' => $playerData['first_name'] ?? null,
                    'last_name' => $playerData['last_name'] ?? null,
                    'college' => $playerData['college'] ?? null,
                    'country' => $playerData['country'] ?? null,
                    'draft_year' => $playerData['draft_year'] ?? null,
                    'draft_round' => $playerData['draft_round'] ?? null,
                    'draft_number' => $playerData['draft_number'] ?? null,
                ],
            ];

            if ($player) {
                $player->fill($attributes)->save();
                $seenIds[] = $player->id;
                $stats['updated']++;
            } else {
                // A genuinely new player: BallDontLie gives no NBA id, so we cannot
                // build a valid headshot. Leave it null rather than link a broken URL.
                $seenIds[] = Player::create($attributes + ['nba_player_id' => null, 'headshot_url' => null])->id;
                $stats['created']++;
            }
        }

        // Deactivate sync-managed NBA players no longer on any active roster.
        // Guarded on a non-empty seen set so a bad/partial fetch can never blank
        // every roster, and scoped to exclude custom (scout-added) players.
        if ($seenIds !== []) {
            $stats['deactivated'] = Player::whereIn('team_id', $nbaTeamIds)
                ->whereNotIn('id', $seenIds)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNotNull('balldontlie_id')->orWhereNotNull('nba_player_id');
                })
                ->update(['is_active' => false]);
        }

        Log::info('SyncPlayersFromBallDontLie: Sync completed', $stats);
    }

    /**
     * A player is sync-managed if it originated from BallDontLie (balldontlie_id)
     * or the NBA seed (nba_player_id). Custom scout-added players have neither and
     * must never be matched, overwritten, or deactivated by this sync.
     */
    protected function isSyncManaged(Player $player): bool
    {
        return $player->balldontlie_id !== null || $player->nba_player_id !== null;
    }

    /**
     * Index players by normalized name, keeping the first row per name.
     *
     * @param  Collection<int, Player>  $players
     * @return Collection<string, Player>
     */
    protected function indexByNormalizedName(Collection $players): Collection
    {
        return $players->reduce(function (Collection $carry, Player $player) {
            $key = $this->normalizeName($player->name);

            if ($key !== '' && ! $carry->has($key)) {
                $carry->put($key, $player);
            }

            return $carry;
        }, collect());
    }

    /**
     * Normalize a name for cross-source matching: lowercase, transliterate
     * accents, drop punctuation and generational suffixes, collapse spaces.
     */
    protected function normalizeName(string $name): string
    {
        $name = Str::of($name)->ascii()->lower()->toString();
        $name = preg_replace('/\b(jr|sr|ii|iii|iv|v)\b/', '', $name);
        $name = preg_replace('/[^a-z0-9 ]/', '', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Format height from "6-8" to "6'8\"" format.
     */
    protected function formatHeight(?string $height): ?string
    {
        if (! $height) {
            return null;
        }

        if (preg_match('/^(\d+)-(\d+)$/', $height, $matches)) {
            return $matches[1]."'".$matches[2].'"';
        }

        return $height;
    }
}
