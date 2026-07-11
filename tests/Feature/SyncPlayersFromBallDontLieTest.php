<?php

namespace Tests\Feature;

use App\Jobs\SyncPlayersFromBallDontLie;
use App\Models\Player;
use App\Models\Team;
use App\Services\BallDontLieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPlayersFromBallDontLieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.balldontlie.key' => 'test-key']);
        Http::preventStrayRequests();
    }

    protected function fakeActivePlayers(array $players): void
    {
        Http::fake([
            'https://api.balldontlie.io/v1/players/active*' => Http::response([
                'data' => $players,
                'meta' => ['next_cursor' => null],
            ]),
        ]);
    }

    protected function bostonTeam(): Team
    {
        return Team::create([
            'name' => 'Boston Celtics',
            'abbreviation' => 'BOS',
            'location' => 'Boston',
            'nickname' => 'Celtics',
            'league' => 'NBA',
            'balldontlie_id' => 2,
        ]);
    }

    public function test_it_matches_seeded_players_by_name_and_preserves_nba_id_and_headshot(): void
    {
        $boston = $this->bostonTeam();

        // Seeded row carries the real NBA id + a valid headshot; no balldontlie_id.
        $seeded = Player::create([
            'team_id' => $boston->id,
            'name' => 'Derrick White',
            'nba_player_id' => 1628401,
            'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/1040x760/1628401.png',
            'jersey' => '9',
            'position' => 'G',
            'is_active' => true,
        ]);

        // BallDontLie returns its OWN id (99), which is NOT the NBA id.
        $this->fakeActivePlayers([
            [
                'id' => 99,
                'first_name' => 'Derrick',
                'last_name' => 'White',
                'position' => 'G',
                'height' => '6-4',
                'weight' => 190,
                'jersey_number' => '9',
                'team' => ['id' => 2],
            ],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        // No duplicate: the seeded row was matched by name.
        $this->assertSame(1, Player::where('name', 'Derrick White')->count());

        $seeded->refresh();
        $this->assertTrue($seeded->is_active);
        $this->assertSame(99, $seeded->balldontlie_id);           // linked going forward
        $this->assertSame(1628401, $seeded->nba_player_id);       // real NBA id preserved
        $this->assertSame(
            'https://cdn.nba.com/headshots/nba/latest/1040x760/1628401.png',
            $seeded->headshot_url                                  // valid headshot preserved
        );
    }

    public function test_it_normalizes_accents_and_suffixes_when_matching(): void
    {
        $boston = $this->bostonTeam();

        $seeded = Player::create([
            'team_id' => $boston->id,
            'name' => 'Luka Dončić',
            'nba_player_id' => 1629029,
            'jersey' => '77',
            'position' => 'G',
            'is_active' => true,
        ]);

        $this->fakeActivePlayers([
            ['id' => 12, 'first_name' => 'Luka', 'last_name' => 'Doncic', 'team' => ['id' => 2]],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        $this->assertSame(1, Player::where('nba_player_id', 1629029)->count());
        $this->assertSame(12, $seeded->fresh()->balldontlie_id);
    }

    public function test_new_players_are_created_without_a_fabricated_headshot(): void
    {
        $this->bostonTeam();

        $this->fakeActivePlayers([
            ['id' => 70, 'first_name' => 'Rookie', 'last_name' => 'Prospect', 'team' => ['id' => 2]],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        $created = Player::where('name', 'Rookie Prospect')->firstOrFail();
        $this->assertTrue($created->is_active);
        $this->assertSame(70, $created->balldontlie_id);
        $this->assertNull($created->nba_player_id);
        $this->assertNull($created->headshot_url);
    }

    public function test_it_never_deactivates_a_custom_scout_added_player(): void
    {
        $boston = $this->bostonTeam();

        // Custom player: no balldontlie_id and no nba_player_id.
        $custom = Player::create([
            'team_id' => $boston->id,
            'name' => 'Local Prospect',
            'jersey' => '99',
            'position' => 'G',
            'is_active' => true,
        ]);

        $this->fakeActivePlayers([
            ['id' => 99, 'first_name' => 'Derrick', 'last_name' => 'White', 'team' => ['id' => 2]],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        $this->assertTrue($custom->fresh()->is_active);
        $this->assertNull($custom->fresh()->balldontlie_id);
    }

    public function test_a_custom_player_is_not_overwritten_by_a_same_named_feed_player(): void
    {
        $boston = $this->bostonTeam();

        $custom = Player::create([
            'team_id' => $boston->id,
            'name' => 'Derrick White',   // deliberately collides with the feed
            'jersey' => '99',
            'position' => 'F',
            'is_active' => true,
        ]);

        $this->fakeActivePlayers([
            [
                'id' => 99,
                'first_name' => 'Derrick',
                'last_name' => 'White',
                'jersey_number' => '9',
                'team' => ['id' => 2],
            ],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        // Custom row untouched; the feed player becomes its own new row.
        $custom->refresh();
        $this->assertNull($custom->balldontlie_id);
        $this->assertSame('99', $custom->jersey);
        $this->assertSame(2, Player::where('name', 'Derrick White')->count());
        $this->assertSame(1, Player::where('name', 'Derrick White')->whereNotNull('balldontlie_id')->count());
    }

    public function test_it_deactivates_departed_nba_players_but_not_other_leagues(): void
    {
        $boston = $this->bostonTeam();

        $departed = Player::create([
            'team_id' => $boston->id,
            'name' => 'Jaylen Brown',
            'nba_player_id' => 1627759,
            'jersey' => '7',
            'position' => 'G',
            'is_active' => true,
        ]);

        $aces = Team::create([
            'name' => 'Las Vegas Aces',
            'abbreviation' => 'LVA',
            'location' => 'Las Vegas',
            'nickname' => 'Aces',
            'league' => 'WNBA',
        ]);

        $wnbaPlayer = Player::create([
            'team_id' => $aces->id,
            'name' => "A'ja Wilson",
            'jersey' => '22',
            'position' => 'F',
            'is_active' => true,
        ]);

        // Active feed does NOT include Jaylen Brown.
        $this->fakeActivePlayers([
            ['id' => 99, 'first_name' => 'Derrick', 'last_name' => 'White', 'team' => ['id' => 2]],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        $this->assertFalse($departed->fresh()->is_active);       // dropped from active roster
        $this->assertSame($boston->id, $departed->fresh()->team_id);
        $this->assertTrue($wnbaPlayer->fresh()->is_active);      // WNBA untouched
    }
}
