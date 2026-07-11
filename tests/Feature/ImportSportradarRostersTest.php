<?php

namespace Tests\Feature;

use App\Models\ExternalId;
use App\Models\Player;
use App\Models\Team;
use App\Services\SportradarBasketballService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportSportradarRostersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sportradar.key' => 'test-key',
            'services.sportradar.access_level' => 'trial',
            'services.sportradar.language' => 'en',
            'services.sportradar.timeout' => 10,
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_imports_g_league_teams_and_rosters_from_sportradar(): void
    {
        $existingTeam = Team::create([
            'name' => 'Sioux Falls Skyforce',
            'abbreviation' => 'SXF',
            'location' => 'Sioux Falls',
            'nickname' => 'Skyforce',
            'league' => 'Foreign',
        ]);

        Http::fake([
            'https://api.sportradar.us/nbdl/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'id' => 'conference-west',
                    'name' => 'Western Conference',
                    'alias' => 'WESTERN',
                    'teams' => [[
                        'id' => 'team-skyforce',
                        'name' => 'Skyforce',
                        'market' => 'Sioux Falls',
                        'alias' => 'SXF',
                        'sr_id' => 'sr:team:1',
                    ]],
                ]],
            ]),
            'https://api.sportradar.us/nbdl/trial/v8/en/teams/team-skyforce/profile.json' => Http::response([
                'id' => 'team-skyforce',
                'players' => [[
                    'id' => 'player-1',
                    'full_name' => 'Kyle Prospect',
                    'jersey_number' => '12',
                    'primary_position' => 'G',
                    'height' => 76,
                    'weight' => 205,
                    'status' => 'ACT',
                ]],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'gleague'])
            ->expectsOutputToContain('Importing G League rosters from Sportradar')
            ->assertExitCode(0);

        $team = Team::where('abbreviation', 'SXF')->firstOrFail();
        $this->assertSame($existingTeam->id, $team->id);
        $this->assertSame('G League', $team->league);
        $this->assertSame('Sioux Falls Skyforce', $team->name);
        $this->assertSame(1, Team::count());

        $player = Player::where('name', 'Kyle Prospect')->firstOrFail();
        $this->assertSame($team->id, $player->team_id);
        $this->assertSame('12', $player->jersey);
        $this->assertSame('G', $player->position);
        $this->assertSame('6\'4"', $player->height);
        $this->assertSame('205 lbs', $player->weight);
        $this->assertTrue($player->is_active);

        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Team::class,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_GLEAGUE,
            'external_id' => 'team-skyforce',
        ]);

        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Player::class,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_GLEAGUE,
            'external_id' => 'player-1',
        ]);
    }

    public function test_it_links_existing_seeded_nba_teams_by_abbreviation_instead_of_duplicating(): void
    {
        $seededTeam = Team::create([
            'name' => 'Boston Celtics',
            'abbreviation' => 'BOS',
            'location' => 'Boston',
            'nickname' => 'Celtics',
            'league' => 'NBA',
        ]);

        $seededPlayer = Player::create([
            'team_id' => $seededTeam->id,
            'name' => 'Derrick White',
            'jersey' => '9',
            'position' => 'G',
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'id' => 'conference-east',
                    'name' => 'Eastern Conference',
                    'alias' => 'EASTERN',
                    'divisions' => [[
                        'id' => 'division-atlantic',
                        'name' => 'Atlantic',
                        'alias' => 'ATLANTIC',
                        'teams' => [[
                            'id' => 'sr-team-celtics',
                            'name' => 'Celtics',
                            'market' => 'Boston',
                            'alias' => 'BOS',
                            'sr_id' => 'sr:team:100',
                        ]],
                    ]],
                ]],
            ]),
            'https://api.sportradar.com/nba/trial/v8/en/teams/sr-team-celtics/profile.json' => Http::response([
                'id' => 'sr-team-celtics',
                'players' => [
                    [
                        'id' => 'sr-player-white',
                        'full_name' => 'Derrick White',
                        'jersey_number' => '9',
                        'primary_position' => 'G',
                        'height' => 76,
                        'weight' => 190,
                        'status' => 'ACT',
                    ],
                    [
                        'id' => 'sr-player-pritchard',
                        'full_name' => 'Payton Pritchard',
                        'jersey_number' => '11',
                        'primary_position' => 'G',
                        'height' => 73,
                        'weight' => 195,
                        'status' => 'ACT',
                    ],
                ],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'nba'])
            ->expectsOutputToContain('Importing NBA rosters from Sportradar')
            ->assertExitCode(0);

        $this->assertSame(1, Team::count());
        $team = Team::where('abbreviation', 'BOS')->firstOrFail();
        $this->assertSame($seededTeam->id, $team->id);
        $this->assertSame('NBA', $team->league);

        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Team::class,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_NBA,
            'external_id' => 'sr-team-celtics',
            'entity_id' => $seededTeam->id,
        ]);

        $matchedPlayer = Player::where('name', 'Derrick White')->firstOrFail();
        $this->assertSame($seededPlayer->id, $matchedPlayer->id);
        $this->assertSame($team->id, $matchedPlayer->team_id);
        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Player::class,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_NBA,
            'external_id' => 'sr-player-white',
        ]);

        $newPlayer = Player::where('name', 'Payton Pritchard')->firstOrFail();
        $this->assertSame($team->id, $newPlayer->team_id);
        $this->assertTrue($newPlayer->is_active);
    }

    public function test_it_carries_a_player_across_leagues_by_name_and_birthdate(): void
    {
        $collegeTeam = Team::create([
            'name' => 'Duke Blue Devils',
            'abbreviation' => 'DUKE',
            'location' => 'Durham',
            'nickname' => 'Blue Devils',
            'league' => 'NCAAB',
        ]);

        $collegePlayer = Player::create([
            'team_id' => $collegeTeam->id,
            'name' => 'Cooper Prospect',
            'jersey' => '2',
            'position' => 'F',
            'birthdate' => '2004-09-04',
            'is_active' => true,
        ]);

        ExternalId::create([
            'entity_type' => Player::class,
            'entity_id' => $collegePlayer->id,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_NCAAMB,
            'external_id' => 'ncaa-player-cooper',
        ]);

        $nbaTeam = Team::create([
            'name' => 'Dallas Mavericks',
            'abbreviation' => 'DAL',
            'location' => 'Dallas',
            'nickname' => 'Mavericks',
            'league' => 'NBA',
        ]);

        Http::fake([
            'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'teams' => [[
                        'id' => 'sr-team-mavs',
                        'name' => 'Mavericks',
                        'market' => 'Dallas',
                        'alias' => 'DAL',
                    ]],
                ]],
            ]),
            'https://api.sportradar.com/nba/trial/v8/en/teams/sr-team-mavs/profile.json' => Http::response([
                'id' => 'sr-team-mavs',
                'players' => [[
                    'id' => 'nba-player-cooper',
                    'full_name' => 'Cooper Prospect',
                    'jersey_number' => '5',
                    'primary_position' => 'F',
                    'birthdate' => '2004-09-04',
                ]],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'nba'])
            ->assertExitCode(0);

        // Same human, same row - not a duplicate.
        $this->assertSame(1, Player::where('name', 'Cooper Prospect')->count());

        $player = $collegePlayer->fresh();
        $this->assertSame($nbaTeam->id, $player->team_id);
        $this->assertTrue($player->is_active);
        $this->assertSame('5', $player->jersey);

        // The row now carries external IDs for both leagues.
        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Player::class,
            'entity_id' => $collegePlayer->id,
            'provider_league' => SportradarBasketballService::LEAGUE_NCAAMB,
            'external_id' => 'ncaa-player-cooper',
        ]);
        $this->assertDatabaseHas('external_ids', [
            'entity_type' => Player::class,
            'entity_id' => $collegePlayer->id,
            'provider_league' => SportradarBasketballService::LEAGUE_NBA,
            'external_id' => 'nba-player-cooper',
        ]);
    }

    public function test_it_does_not_merge_different_players_who_share_a_name(): void
    {
        $collegeTeam = Team::create([
            'name' => 'Duke Blue Devils',
            'abbreviation' => 'DUKE',
            'location' => 'Durham',
            'nickname' => 'Blue Devils',
            'league' => 'NCAAB',
        ]);

        $collegePlayer = Player::create([
            'team_id' => $collegeTeam->id,
            'name' => 'Chris Johnson',
            'jersey' => '2',
            'position' => 'G',
            'birthdate' => '2003-01-15',
            'is_active' => true,
        ]);

        $nbaTeam = Team::create([
            'name' => 'Dallas Mavericks',
            'abbreviation' => 'DAL',
            'location' => 'Dallas',
            'nickname' => 'Mavericks',
            'league' => 'NBA',
        ]);

        Http::fake([
            'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'teams' => [[
                        'id' => 'sr-team-mavs',
                        'name' => 'Mavericks',
                        'market' => 'Dallas',
                        'alias' => 'DAL',
                    ]],
                ]],
            ]),
            'https://api.sportradar.com/nba/trial/v8/en/teams/sr-team-mavs/profile.json' => Http::response([
                'id' => 'sr-team-mavs',
                'players' => [[
                    'id' => 'nba-player-different-chris',
                    'full_name' => 'Chris Johnson',
                    'jersey_number' => '5',
                    'primary_position' => 'F',
                    'birthdate' => '1996-07-21',
                ]],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'nba'])
            ->assertExitCode(0);

        // Different birthdate => two distinct people, two rows.
        $this->assertSame(2, Player::where('name', 'Chris Johnson')->count());
        $this->assertSame($collegeTeam->id, $collegePlayer->fresh()->team_id);
    }

    public function test_dry_run_fetches_but_does_not_write(): void
    {
        Http::fake([
            'https://api.sportradar.com/ncaamb/trial/v8/en/league/hierarchy.json' => Http::response([
                'divisions' => [
                    [
                        'id' => 'division-d1',
                        'name' => 'NCAA Division I',
                        'alias' => 'D1',
                        'conferences' => [[
                            'id' => 'conference-a',
                            'name' => 'Conference A',
                            'teams' => [[
                                'id' => 'team-college',
                                'name' => 'Bulldogs',
                                'market' => 'Example College',
                                'alias' => 'EXC',
                            ]],
                        ]],
                    ],
                    [
                        'id' => 'division-d2',
                        'name' => 'NCAA Division II',
                        'alias' => 'D2',
                        'conferences' => [[
                            'teams' => [[
                                'id' => 'team-d2',
                                'name' => 'Skipped',
                                'market' => 'Lower Division',
                                'alias' => 'LOW',
                            ]],
                        ]],
                    ],
                ],
            ]),
            'https://api.sportradar.com/ncaamb/trial/v8/en/teams/team-college/profile.json' => Http::response([
                'players' => [[
                    'id' => 'player-college',
                    'full_name' => 'Nate Freshman',
                ]],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'ncaamb', '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);

        $this->assertSame(0, Team::count());
        $this->assertSame(0, Player::count());
        $this->assertSame(0, ExternalId::count());
    }

    public function test_it_marks_players_missing_from_the_fetched_roster_inactive_but_only_on_the_imported_team(): void
    {
        $team = Team::create([
            'name' => 'Sioux Falls Skyforce',
            'abbreviation' => 'SXF',
            'location' => 'Sioux Falls',
            'nickname' => 'Skyforce',
            'league' => 'G League',
        ]);

        ExternalId::create([
            'entity_type' => Team::class,
            'entity_id' => $team->id,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_GLEAGUE,
            'external_id' => 'team-skyforce',
        ]);

        $stalePlayer = Player::create([
            'team_id' => $team->id,
            'name' => 'Old Assignment',
            'jersey' => '1',
            'position' => 'G',
            'is_active' => true,
        ]);

        ExternalId::create([
            'entity_type' => Player::class,
            'entity_id' => $stalePlayer->id,
            'provider' => SportradarBasketballService::PROVIDER,
            'provider_league' => SportradarBasketballService::LEAGUE_GLEAGUE,
            'external_id' => 'stale-player',
        ]);

        // A hand-seeded straggler with no provider link (e.g. a player traded away
        // before the team was ever synced) must also be deactivated on import.
        $seededStraggler = Player::create([
            'team_id' => $team->id,
            'name' => 'Traded Away',
            'jersey' => '3',
            'position' => 'C',
            'is_active' => true,
        ]);

        $nbaTeam = Team::create([
            'name' => 'Golden State Warriors',
            'abbreviation' => 'GSW',
            'location' => 'Golden State',
            'nickname' => 'Warriors',
            'league' => 'NBA',
        ]);

        $nbaPlayer = Player::create([
            'team_id' => $nbaTeam->id,
            'name' => 'Active NBA Player',
            'jersey' => '30',
            'position' => 'G',
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.sportradar.us/nbdl/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'teams' => [[
                        'id' => 'team-skyforce',
                        'name' => 'Skyforce',
                        'market' => 'Sioux Falls',
                        'alias' => 'SXF',
                    ]],
                ]],
            ]),
            'https://api.sportradar.us/nbdl/trial/v8/en/teams/team-skyforce/profile.json' => Http::response([
                'players' => [[
                    'id' => 'current-player',
                    'full_name' => 'Current Assignment',
                    'jersey_number' => '2',
                    'primary_position' => 'F',
                ]],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'gleague'])
            ->assertExitCode(0);

        $this->assertFalse($stalePlayer->fresh()->is_active);
        $this->assertFalse($seededStraggler->fresh()->is_active);
        $this->assertTrue($nbaPlayer->fresh()->is_active);
        $this->assertTrue(Player::where('name', 'Current Assignment')->firstOrFail()->is_active);
    }

    public function test_an_empty_roster_response_does_not_deactivate_existing_players(): void
    {
        $team = Team::create([
            'name' => 'Boston Celtics',
            'abbreviation' => 'BOS',
            'location' => 'Boston',
            'nickname' => 'Celtics',
            'league' => 'NBA',
        ]);

        $player = Player::create([
            'team_id' => $team->id,
            'name' => 'Jayson Tatum',
            'jersey' => '0',
            'position' => 'F',
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.sportradar.com/nba/trial/v8/en/league/hierarchy.json' => Http::response([
                'conferences' => [[
                    'teams' => [[
                        'id' => 'sr-team-celtics',
                        'name' => 'Celtics',
                        'market' => 'Boston',
                        'alias' => 'BOS',
                    ]],
                ]],
            ]),
            'https://api.sportradar.com/nba/trial/v8/en/teams/sr-team-celtics/profile.json' => Http::response([
                'id' => 'sr-team-celtics',
                'players' => [],
            ]),
        ]);

        $this->artisan('app:import-sportradar-rosters', ['league' => 'nba'])
            ->assertExitCode(0);

        $this->assertTrue($player->fresh()->is_active);
    }
}
