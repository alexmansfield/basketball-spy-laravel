<?php

namespace App\Console\Commands;

use App\Models\ExternalId;
use App\Models\Player;
use App\Models\Team;
use App\Services\SportradarBasketballService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class ImportSportradarRosters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-sportradar-rosters
                            {league=all : ncaamb, gleague, or all}
                            {--dry-run : Fetch and report changes without writing to the database}
                            {--limit= : Limit teams per league for smoke tests}
                            {--team= : Import one team by Sportradar UUID, abbreviation, market, or name}
                            {--division= : NCAA division alias/name to import; defaults to D1 for NCAAB. Use "all" for every division}
                            {--sleep-ms=1100 : Milliseconds to pause between team profile requests}
                            {--skip-teams : Use existing teams with Sportradar external IDs instead of fetching league hierarchy}
                            {--keep-stale-active : Do not mark provider-linked players missing from fetched rosters inactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import G League and NCAA men\'s basketball rosters from Sportradar';

    protected array $stats = [];

    /**
     * Execute the console command.
     */
    public function handle(SportradarBasketballService $sportradar): int
    {
        if (! $sportradar->configured()) {
            $this->error('SPORTRADAR_API_KEY is not configured.');

            return Command::FAILURE;
        }

        try {
            $leagues = $this->resolveLeagues((string) $this->argument('league'), $sportradar);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no database changes will be written.');
        }

        $this->resetStats();

        foreach ($leagues as $league) {
            $this->importLeague($sportradar, $league, $dryRun);
        }

        if (! $dryRun && $this->stats['players_created'] + $this->stats['players_updated'] + $this->stats['teams_created'] + $this->stats['teams_updated'] > 0) {
            Cache::flush();
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($this->stats)->map(fn ($value, $key) => [Str::headline($key), $value])->values()->all()
        );

        return Command::SUCCESS;
    }

    protected function resolveLeagues(string $league, SportradarBasketballService $sportradar): array
    {
        if (strtolower($league) === 'all') {
            return $sportradar->supportedLeagues();
        }

        return [$sportradar->normalizeLeague($league)];
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'teams_created' => 0,
            'teams_updated' => 0,
            'teams_skipped' => 0,
            'profiles_fetched' => 0,
            'players_created' => 0,
            'players_updated' => 0,
            'players_skipped' => 0,
            'players_marked_inactive' => 0,
        ];
    }

    protected function importLeague(SportradarBasketballService $sportradar, string $league, bool $dryRun): void
    {
        $displayLeague = $sportradar->displayLeague($league);
        $this->newLine();
        $this->info("Importing {$displayLeague} rosters from Sportradar...");

        $teams = $this->option('skip-teams')
            ? $this->existingSportradarTeams($league)
            : $sportradar->getTeams($league);

        $teams = $this->filterTeamsByDivision($league, $teams);
        $teams = $this->filterTeams($teams);

        if ($teams === []) {
            $this->warn("No {$displayLeague} teams matched the import criteria.");

            return;
        }

        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        if ($limit !== null) {
            $teams = array_slice($teams, 0, $limit);
        }

        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();

        foreach ($teams as $index => $teamData) {
            try {
                [$team, $teamStatus] = $this->upsertTeam($league, $displayLeague, $teamData, $dryRun);
                $this->incrementTeamStat($teamStatus);

                $profile = $sportradar->getTeamProfile($league, (string) $teamData['id']);
                $this->stats['profiles_fetched']++;

                $seenPlayerIds = $this->upsertRoster(
                    $league,
                    $team,
                    $teamData,
                    $sportradar->extractPlayersFromProfile($profile),
                    $dryRun
                );

                $this->markStalePlayersInactive($league, $team, $seenPlayerIds, $dryRun);
            } catch (RuntimeException $e) {
                $this->stats['teams_skipped']++;
                $this->newLine();
                $this->warn("Skipped team [{$teamData['id']}]: {$e->getMessage()}");
            }

            $bar->advance();

            $sleepMs = max(0, (int) $this->option('sleep-ms'));
            if ($sleepMs > 0 && $index < count($teams) - 1) {
                usleep($sleepMs * 1000);
            }
        }

        $bar->finish();
        $this->newLine();
    }

    protected function existingSportradarTeams(string $league): array
    {
        return ExternalId::query()
            ->where('entity_type', Team::class)
            ->where('provider', SportradarBasketballService::PROVIDER)
            ->where('provider_league', $league)
            ->get()
            ->map(function (ExternalId $externalId) {
                $team = Team::withTrashed()->find($externalId->entity_id);

                if (! $team) {
                    return null;
                }

                return [
                    'id' => $externalId->external_id,
                    'name' => $team->nickname,
                    'market' => $team->location,
                    'alias' => $team->abbreviation,
                    '_context' => $team->extra_attributes['sportradar']['hierarchy'] ?? [],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function filterTeams(array $teams): array
    {
        $filter = $this->option('team');
        if (! $filter) {
            return $teams;
        }

        $filter = Str::of((string) $filter)->lower()->toString();

        return array_values(array_filter($teams, function (array $team) use ($filter) {
            $values = [
                $team['id'] ?? null,
                $team['alias'] ?? null,
                $team['abbreviation'] ?? null,
                $team['market'] ?? null,
                $team['name'] ?? null,
                $this->fullTeamName($team),
            ];

            foreach ($values as $value) {
                if ($value !== null && Str::of((string) $value)->lower()->toString() === $filter) {
                    return true;
                }
            }

            return false;
        }));
    }

    protected function filterTeamsByDivision(string $league, array $teams): array
    {
        if ($league !== SportradarBasketballService::LEAGUE_NCAAMB) {
            return $teams;
        }

        $division = $this->option('division') ?: 'D1';
        $division = Str::of((string) $division)->lower()->trim()->toString();

        if ($division === 'all') {
            return $teams;
        }

        return array_values(array_filter($teams, function (array $team) use ($division) {
            $context = $team['_context'] ?? [];

            $values = [
                $context['division_alias'] ?? null,
                $context['division_name'] ?? null,
            ];

            foreach ($values as $value) {
                if ($value !== null && Str::of((string) $value)->lower()->trim()->toString() === $division) {
                    return true;
                }
            }

            return false;
        }));
    }

    protected function upsertTeam(string $league, string $displayLeague, array $teamData, bool $dryRun): array
    {
        $externalId = (string) ($teamData['id'] ?? '');
        if ($externalId === '') {
            throw new RuntimeException('Team is missing a Sportradar ID.');
        }

        $team = $this->findByExternalId(Team::class, $league, $externalId)
            ?? $this->findExistingTeam($league, $teamData);

        $status = $team ? 'updated' : 'created';
        $attributes = $this->teamAttributes($displayLeague, $teamData, $team);

        if ($dryRun) {
            return [$team, $status];
        }

        if ($team) {
            $team->fill($attributes);
            $team->save();

            if (method_exists($team, 'restore') && $team->trashed()) {
                $team->restore();
            }
        } else {
            $team = Team::create($attributes);
        }

        $this->storeExternalId($team, $league, $externalId);

        return [$team, $status];
    }

    protected function upsertRoster(string $league, ?Team $team, array $teamData, array $players, bool $dryRun): array
    {
        $seenPlayerIds = [];

        foreach ($players as $playerData) {
            if (! is_array($playerData)) {
                $this->stats['players_skipped']++;

                continue;
            }

            try {
                [$player, $status] = $this->upsertPlayer($league, $team, $teamData, $playerData, $dryRun);

                if ($player) {
                    $seenPlayerIds[] = $player->id;
                }

                $this->incrementPlayerStat($status);
            } catch (RuntimeException) {
                $this->stats['players_skipped']++;
            }
        }

        return $seenPlayerIds;
    }

    protected function upsertPlayer(string $league, ?Team $team, array $teamData, array $playerData, bool $dryRun): array
    {
        $externalId = (string) ($playerData['id'] ?? '');
        $name = $this->playerName($playerData);

        if ($externalId === '' || $name === '') {
            throw new RuntimeException('Player is missing a required identity field.');
        }

        $player = $this->findByExternalId(Player::class, $league, $externalId);

        if (! $player && $team) {
            $player = Player::withTrashed()
                ->where('team_id', $team->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();
        }

        $status = $player ? 'updated' : 'created';

        if ($dryRun) {
            return [$player, $status];
        }

        if (! $team) {
            throw new RuntimeException("Cannot import player [{$name}] because the team does not exist locally.");
        }

        $attributes = $this->playerAttributes($team, $teamData, $playerData, $player);

        if ($player) {
            $player->fill($attributes);
            $player->save();

            if (method_exists($player, 'restore') && $player->trashed()) {
                $player->restore();
            }
        } else {
            $player = Player::create($attributes);
        }

        $this->storeExternalId($player, $league, $externalId);

        return [$player, $status];
    }

    protected function markStalePlayersInactive(string $league, ?Team $team, array $seenPlayerIds, bool $dryRun): void
    {
        if ($this->option('keep-stale-active') || ! $team) {
            return;
        }

        $query = Player::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->whereHas('externalIds', function ($query) use ($league) {
                $query
                    ->where('provider', SportradarBasketballService::PROVIDER)
                    ->where('provider_league', $league);
            });

        if ($seenPlayerIds !== []) {
            $query->whereNotIn('id', $seenPlayerIds);
        }

        $count = (clone $query)->count();
        $this->stats['players_marked_inactive'] += $count;

        if (! $dryRun && $count > 0) {
            $query->update(['is_active' => false]);
        }
    }

    protected function findByExternalId(string $modelClass, string $league, string $externalId): ?Model
    {
        $record = ExternalId::query()
            ->where('entity_type', $modelClass)
            ->where('provider', SportradarBasketballService::PROVIDER)
            ->where('provider_league', $league)
            ->where('external_id', $externalId)
            ->first();

        if (! $record) {
            return null;
        }

        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $query->find($record->entity_id);
    }

    protected function findExistingTeam(string $league, array $teamData): ?Team
    {
        $aliases = $this->leagueAliases($league);
        $abbreviation = Str::lower($this->teamAbbreviation($teamData));
        $fullName = Str::lower($this->fullTeamName($teamData));

        $query = Team::withTrashed()->whereIn('league', $aliases);

        if ($abbreviation !== '') {
            $team = (clone $query)
                ->whereRaw('LOWER(abbreviation) = ?', [$abbreviation])
                ->first();

            if ($team) {
                return $team;
            }
        }

        return $query
            ->whereRaw('LOWER(name) = ?', [$fullName])
            ->first();
    }

    protected function storeExternalId(Model $model, string $league, string $externalId): void
    {
        ExternalId::updateOrCreate(
            [
                'entity_type' => $model::class,
                'provider' => SportradarBasketballService::PROVIDER,
                'provider_league' => $league,
                'external_id' => $externalId,
            ],
            [
                'entity_id' => $model->getKey(),
            ]
        );
    }

    protected function teamAttributes(string $displayLeague, array $teamData, ?Team $existingTeam): array
    {
        $context = $teamData['_context'] ?? [];
        $existingExtra = $existingTeam?->extra_attributes ?? [];

        return [
            'name' => $this->fullTeamName($teamData),
            'abbreviation' => $this->teamAbbreviation($teamData),
            'location' => $this->teamMarket($teamData),
            'nickname' => $this->teamNickname($teamData),
            'league' => $displayLeague,
            'extra_attributes' => array_replace($existingExtra, [
                'sportradar' => [
                    'id' => $teamData['id'] ?? null,
                    'sr_id' => $teamData['sr_id'] ?? null,
                    'reference' => $teamData['reference'] ?? null,
                    'hierarchy' => $context,
                ],
            ]),
        ];
    }

    protected function playerAttributes(Team $team, array $teamData, array $playerData, ?Player $existingPlayer): array
    {
        $existingExtra = $existingPlayer?->extra_attributes ?? [];

        return [
            'team_id' => $team->id,
            'name' => $this->playerName($playerData),
            'jersey' => (string) ($playerData['jersey_number'] ?? $playerData['jersey'] ?? ''),
            'position' => (string) ($playerData['primary_position'] ?? $playerData['position'] ?? ''),
            'height' => $this->formatHeight($playerData['height'] ?? null),
            'weight' => $this->formatWeight($playerData['weight'] ?? null),
            'birthdate' => $playerData['birthdate'] ?? $existingPlayer?->birthdate,
            'headshot_url' => $existingPlayer?->headshot_url,
            'is_active' => true,
            'extra_attributes' => array_replace($existingExtra, [
                'sportradar' => [
                    'id' => $playerData['id'] ?? null,
                    'sr_id' => $playerData['sr_id'] ?? null,
                    'reference' => $playerData['reference'] ?? null,
                    'status' => $playerData['status'] ?? null,
                    'experience' => $playerData['experience'] ?? null,
                    'year' => $playerData['year'] ?? null,
                    'first_name' => $playerData['first_name'] ?? null,
                    'last_name' => $playerData['last_name'] ?? null,
                    'team_id' => $teamData['id'] ?? null,
                ],
            ]),
        ];
    }

    protected function fullTeamName(array $teamData): string
    {
        $market = $this->teamMarket($teamData);
        $nickname = $this->teamNickname($teamData);

        if ($market !== '' && $nickname !== '' && ! Str::contains(Str::lower($nickname), Str::lower($market))) {
            return trim("{$market} {$nickname}");
        }

        return $nickname !== '' ? $nickname : $market;
    }

    protected function teamMarket(array $teamData): string
    {
        return trim((string) ($teamData['market'] ?? $teamData['location'] ?? $teamData['name'] ?? ''));
    }

    protected function teamNickname(array $teamData): string
    {
        return trim((string) ($teamData['name'] ?? $teamData['market'] ?? ''));
    }

    protected function teamAbbreviation(array $teamData): string
    {
        $abbreviation = trim((string) ($teamData['alias'] ?? $teamData['abbreviation'] ?? ''));

        if ($abbreviation === '') {
            $abbreviation = Str::of($this->teamMarket($teamData) ?: $this->teamNickname($teamData))
                ->ascii()
                ->upper()
                ->replaceMatches('/[^A-Z0-9]/', '')
                ->substr(0, 10)
                ->toString();
        }

        return Str::of($abbreviation)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->substr(0, 10)
            ->toString();
    }

    protected function playerName(array $playerData): string
    {
        $name = trim((string) ($playerData['full_name'] ?? $playerData['name'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        return trim((string) (($playerData['first_name'] ?? '').' '.($playerData['last_name'] ?? '')));
    }

    protected function formatHeight(mixed $height): ?string
    {
        if ($height === null || $height === '') {
            return null;
        }

        if (is_numeric($height)) {
            $height = (int) $height;

            return intdiv($height, 12)."'".($height % 12).'"';
        }

        $height = (string) $height;

        if (preg_match('/^(\d+)-(\d+)$/', $height, $matches)) {
            return $matches[1]."'".$matches[2].'"';
        }

        return $height;
    }

    protected function formatWeight(mixed $weight): ?string
    {
        if ($weight === null || $weight === '') {
            return null;
        }

        return is_numeric($weight) ? "{$weight} lbs" : (string) $weight;
    }

    protected function leagueAliases(string $league): array
    {
        return match ($league) {
            SportradarBasketballService::LEAGUE_GLEAGUE => ['G League', 'NBA G League', 'G-League', 'NBAGL', 'NBDL'],
            SportradarBasketballService::LEAGUE_NCAAMB => ['NCAAB', 'NCAA', 'NCAA Basketball', 'College'],
            default => [],
        };
    }

    protected function incrementTeamStat(string $status): void
    {
        if ($status === 'created') {
            $this->stats['teams_created']++;
        } elseif ($status === 'updated') {
            $this->stats['teams_updated']++;
        }
    }

    protected function incrementPlayerStat(string $status): void
    {
        if ($status === 'created') {
            $this->stats['players_created']++;
        } elseif ($status === 'updated') {
            $this->stats['players_updated']++;
        }
    }
}
