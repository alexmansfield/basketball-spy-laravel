<?php

namespace Tests\Feature;

use App\Jobs\SyncPlayersFromEspn;
use App\Models\Player;
use App\Models\Team;
use App\Services\EspnBasketballService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPlayersFromEspnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function boston(): Team
    {
        return Team::create([
            'name' => 'Boston Celtics', 'abbreviation' => 'BOS', 'location' => 'Boston',
            'nickname' => 'Celtics', 'league' => 'NBA',
        ]);
    }

    protected function sixers(): Team
    {
        return Team::create([
            'name' => 'Philadelphia 76ers', 'abbreviation' => 'PHI', 'location' => 'Philadelphia',
            'nickname' => '76ers', 'league' => 'NBA',
        ]);
    }

    /**
     * Fake ESPN's teams list and each team's roster endpoint.
     *
     * @param  array<string, int>  $teams  abbreviation => espn team id
     * @param  array<int, array<int, array<string, mixed>>>  $rosters  espn team id => athletes
     */
    protected function fakeEspn(array $teams, array $rosters): void
    {
        $base = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba';

        $fakes = [
            "{$base}/teams" => Http::response([
                'sports' => [['leagues' => [['teams' => collect($teams)->map(fn ($id, $abbr) => [
                    'team' => ['id' => (string) $id, 'abbreviation' => $abbr, 'displayName' => $abbr],
                ])->values()->all()]]]],
            ]),
        ];

        foreach ($rosters as $teamId => $athletes) {
            $fakes["{$base}/teams/{$teamId}/roster"] = Http::response(['athletes' => $athletes]);
        }

        Http::fake($fakes);
    }

    protected function athlete(int $id, string $name, string $dob, string $jersey = '0'): array
    {
        return [
            'id' => $id,
            'fullName' => $name,
            'displayName' => $name,
            'jersey' => $jersey,
            'position' => ['abbreviation' => 'G'],
            'displayHeight' => "6' 6\"",
            'displayWeight' => '223 lbs',
            'dateOfBirth' => $dob.'T07:00Z',
            'headshot' => ['href' => "https://a.espncdn.com/i/headshots/nba/players/full/{$id}.png"],
        ];
    }

    protected function sync(): void
    {
        (new SyncPlayersFromEspn)->handle(app(EspnBasketballService::class));
    }

    public function test_it_links_existing_player_by_name_and_birthdate_and_follows_a_trade(): void
    {
        $bos = $this->boston();
        $phi = $this->sixers();

        // Recovered/seeded row: real NBA id, birthdate, no espn_id, on Boston.
        $seeded = Player::create([
            'team_id' => $bos->id,
            'name' => 'Jaylen Brown',
            'nba_player_id' => 1627759,
            'birthdate' => '1996-10-24',
            'jersey' => '7',
            'position' => 'G',
            'is_active' => true,
        ]);

        // ESPN now lists him on the 76ers with ESPN id 3917376.
        $this->fakeEspn(
            ['BOS' => 2, 'PHI' => 20],
            [2 => [], 20 => [$this->athlete(3917376, 'Jaylen Brown', '1996-10-24', '7')]],
        );

        $this->sync();

        // No duplicate: same row, matched by name + birthdate.
        $this->assertSame(1, Player::where('name', 'Jaylen Brown')->count());

        $seeded->refresh();
        $this->assertSame($phi->id, $seeded->team_id);                 // followed the trade
        $this->assertSame(3917376, $seeded->espn_id);                  // now carries espn_id
        $this->assertSame(1627759, $seeded->nba_player_id);            // real NBA id preserved
        $this->assertTrue($seeded->is_active);
        $this->assertSame('https://a.espncdn.com/i/headshots/nba/players/full/3917376.png', $seeded->headshot_url);
    }

    public function test_a_second_run_is_idempotent_via_espn_id(): void
    {
        $bos = $this->boston();

        $player = Player::create([
            'team_id' => $bos->id, 'name' => 'Jayson Tatum', 'espn_id' => 4065648,
            'birthdate' => '1998-03-03', 'jersey' => '0', 'position' => 'F', 'is_active' => true,
        ]);

        $this->fakeEspn(['BOS' => 2], [2 => [$this->athlete(4065648, 'Jayson Tatum', '1998-03-03', '0')]]);

        $this->sync();
        $this->sync();

        $this->assertSame(1, Player::where('espn_id', 4065648)->count());
        $this->assertSame($player->id, Player::where('espn_id', 4065648)->firstOrFail()->id);
    }

    public function test_it_creates_new_players_with_espn_headshot_and_birthdate(): void
    {
        $this->boston();

        $this->fakeEspn(['BOS' => 2], [2 => [$this->athlete(999999, 'Rookie Prospect', '2005-01-15', '30')]]);

        $this->sync();

        $created = Player::where('espn_id', 999999)->firstOrFail();
        $this->assertSame('Rookie Prospect', $created->name);
        $this->assertTrue($created->is_active);
        $this->assertSame('2005-01-15', $created->birthdate->format('Y-m-d'));
        $this->assertSame('https://a.espncdn.com/i/headshots/nba/players/full/999999.png', $created->headshot_url);
        $this->assertNull($created->nba_player_id);
    }

    public function test_it_deactivates_departed_players_but_never_custom_players(): void
    {
        $bos = $this->boston();

        // Provider-managed player no longer on any ESPN roster.
        $departed = Player::create([
            'team_id' => $bos->id, 'name' => 'Traded Away', 'nba_player_id' => 111,
            'jersey' => '5', 'position' => 'G', 'is_active' => true,
        ]);

        // Custom scout-added player: no provider id at all.
        $custom = Player::create([
            'team_id' => $bos->id, 'name' => 'Local Prospect', 'jersey' => '99',
            'position' => 'F', 'is_active' => true,
        ]);

        $this->fakeEspn(['BOS' => 2], [2 => [$this->athlete(555, 'Derrick White', '1994-07-02', '9')]]);

        $this->sync();

        $this->assertFalse($departed->fresh()->is_active);   // deactivated, not deleted
        $this->assertTrue($custom->fresh()->is_active);      // custom untouched
        $this->assertNull($custom->fresh()->espn_id);
    }
}
