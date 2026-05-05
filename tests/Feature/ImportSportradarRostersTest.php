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

    public function test_it_only_marks_stale_provider_linked_players_in_the_imported_league_inactive(): void
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
        $this->assertTrue($nbaPlayer->fresh()->is_active);
        $this->assertTrue(Player::where('name', 'Current Assignment')->firstOrFail()->is_active);
    }
}
