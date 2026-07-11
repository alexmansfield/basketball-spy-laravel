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

    public function test_it_updates_seeded_nba_players_in_place_and_deactivates_departed_ones(): void
    {
        $boston = Team::create([
            'name' => 'Boston Celtics',
            'abbreviation' => 'BOS',
            'location' => 'Boston',
            'nickname' => 'Celtics',
            'league' => 'NBA',
            'balldontlie_id' => 2,
        ]);

        // Seeded rows carry nba_player_id but no balldontlie_id yet.
        $departed = Player::create([
            'team_id' => $boston->id,
            'name' => 'Jaylen Brown',
            'nba_player_id' => 1627759,
            'jersey' => '7',
            'position' => 'G',
            'is_active' => true,
        ]);

        $returning = Player::create([
            'team_id' => $boston->id,
            'name' => 'Derrick White',
            'nba_player_id' => 1628401,
            'jersey' => '9',
            'position' => 'G',
            'is_active' => true,
        ]);

        $this->fakeActivePlayers([
            [
                'id' => 1628401,
                'first_name' => 'Derrick',
                'last_name' => 'White',
                'position' => 'G',
                'height' => '6-4',
                'weight' => 190,
                'jersey_number' => '9',
                'team' => ['id' => 2],
            ],
            [
                'id' => 1629014,
                'first_name' => 'Anfernee',
                'last_name' => 'Simons',
                'position' => 'G',
                'height' => '6-3',
                'weight' => 181,
                'jersey_number' => '1',
                'team' => ['id' => 2],
            ],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        // Departed player: not in the active feed -> deactivated, same row, still Boston.
        $departed->refresh();
        $this->assertFalse($departed->is_active);
        $this->assertSame($boston->id, $departed->team_id);

        // Returning seeded player: matched by nba_player_id, not duplicated.
        $this->assertSame(1, Player::where('nba_player_id', 1628401)->count());
        $returning->refresh();
        $this->assertTrue($returning->is_active);
        $this->assertSame(1628401, $returning->balldontlie_id);

        // New active player is created on Boston.
        $created = Player::where('nba_player_id', 1629014)->firstOrFail();
        $this->assertSame($boston->id, $created->team_id);
        $this->assertTrue($created->is_active);
    }

    public function test_it_does_not_deactivate_players_in_other_leagues(): void
    {
        $boston = Team::create([
            'name' => 'Boston Celtics',
            'abbreviation' => 'BOS',
            'location' => 'Boston',
            'nickname' => 'Celtics',
            'league' => 'NBA',
            'balldontlie_id' => 2,
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

        $this->fakeActivePlayers([
            [
                'id' => 1628401,
                'first_name' => 'Derrick',
                'last_name' => 'White',
                'position' => 'G',
                'team' => ['id' => 2],
            ],
        ]);

        (new SyncPlayersFromBallDontLie)->handle(app(BallDontLieService::class));

        $this->assertTrue($wnbaPlayer->fresh()->is_active);
    }
}
