<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    /**
     * Seed sample games for today and upcoming days.
     * Creates realistic NBA game schedules for testing.
     */
    public function run(): void
    {
        $today = Carbon::today();

        // Get team IDs by abbreviation for easy reference
        $teams = Team::pluck('id', 'abbreviation')->toArray();

        if (empty($teams)) {
            $this->command->warn('No teams found. Please run NBATeamsSeeder first.');
            return;
        }

        // Today's games - includes Orlando Magic for Lake Buena Vista testing
        $todaysGames = [
            // Orlando Magic home game - will show as "NEARBY" for Lake Buena Vista
            [
                'home_team' => 'ORL',
                'away_team' => 'MIA',
                'time' => '19:00:00',
                'status' => 'scheduled',
            ],
            // Other games around the league
            [
                'home_team' => 'LAL',
                'away_team' => 'BOS',
                'time' => '22:30:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'GSW',
                'away_team' => 'PHX',
                'time' => '22:00:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'NYK',
                'away_team' => 'BKN',
                'time' => '19:30:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'CHI',
                'away_team' => 'MIL',
                'time' => '20:00:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'DAL',
                'away_team' => 'HOU',
                'time' => '20:30:00',
                'status' => 'scheduled',
            ],
        ];

        // Tomorrow's games
        $tomorrowsGames = [
            [
                'home_team' => 'ORL',
                'away_team' => 'ATL',
                'time' => '19:00:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'DEN',
                'away_team' => 'LAC',
                'time' => '21:00:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'MIN',
                'away_team' => 'OKC',
                'time' => '20:00:00',
                'status' => 'scheduled',
            ],
            [
                'home_team' => 'TOR',
                'away_team' => 'PHI',
                'time' => '19:30:00',
                'status' => 'scheduled',
            ],
        ];

        // Seed today's games
        foreach ($todaysGames as $game) {
            $homeTeamId = $teams[$game['home_team']] ?? null;
            $awayTeamId = $teams[$game['away_team']] ?? null;

            if ($homeTeamId && $awayTeamId) {
                Game::updateOrCreate(
                    [
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'scheduled_at' => $today->copy()->setTimeFromTimeString($game['time']),
                    ],
                    [
                        'status' => $game['status'],
                        'external_id' => 'seed-' . $game['home_team'] . '-' . $game['away_team'] . '-' . $today->format('Ymd'),
                    ]
                );

                $this->command->info("Created game: {$game['away_team']} @ {$game['home_team']} - {$today->format('Y-m-d')} {$game['time']}");
            } else {
                $this->command->warn("Could not find teams: {$game['home_team']} or {$game['away_team']}");
            }
        }

        // Seed tomorrow's games
        $tomorrow = $today->copy()->addDay();
        foreach ($tomorrowsGames as $game) {
            $homeTeamId = $teams[$game['home_team']] ?? null;
            $awayTeamId = $teams[$game['away_team']] ?? null;

            if ($homeTeamId && $awayTeamId) {
                Game::updateOrCreate(
                    [
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'scheduled_at' => $tomorrow->copy()->setTimeFromTimeString($game['time']),
                    ],
                    [
                        'status' => $game['status'],
                        'external_id' => 'seed-' . $game['home_team'] . '-' . $game['away_team'] . '-' . $tomorrow->format('Ymd'),
                    ]
                );

                $this->command->info("Created game: {$game['away_team']} @ {$game['home_team']} - {$tomorrow->format('Y-m-d')} {$game['time']}");
            }
        }

        $this->command->info('Games seeding complete!');
    }
}
