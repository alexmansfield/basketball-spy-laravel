<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class WNBATeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds all 12 WNBA teams with official logos from WNBA CDN
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Atlanta Dream',
                'abbreviation' => 'ATL',
                'location' => 'Atlanta',
                'nickname' => 'Dream',
                'league' => 'WNBA',
                'url' => 'https://dream.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661317/primary/L/logo.svg',
                'color' => '#C8102E',
            ],
            [
                'name' => 'Chicago Sky',
                'abbreviation' => 'CHI',
                'location' => 'Chicago',
                'nickname' => 'Sky',
                'league' => 'WNBA',
                'url' => 'https://sky.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661325/primary/L/logo.svg',
                'color' => '#5091CD',
            ],
            [
                'name' => 'Connecticut Sun',
                'abbreviation' => 'CON',
                'location' => 'Connecticut',
                'nickname' => 'Sun',
                'league' => 'WNBA',
                'url' => 'https://sun.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661330/primary/L/logo.svg',
                'color' => '#FF6600',
            ],
            [
                'name' => 'Dallas Wings',
                'abbreviation' => 'DAL',
                'location' => 'Dallas',
                'nickname' => 'Wings',
                'league' => 'WNBA',
                'url' => 'https://wings.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661320/primary/L/logo.svg',
                'color' => '#0C2340',
            ],
            [
                'name' => 'Indiana Fever',
                'abbreviation' => 'IND',
                'location' => 'Indiana',
                'nickname' => 'Fever',
                'league' => 'WNBA',
                'url' => 'https://fever.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661313/primary/L/logo.svg',
                'color' => '#002D62',
            ],
            [
                'name' => 'Las Vegas Aces',
                'abbreviation' => 'LV',
                'location' => 'Las Vegas',
                'nickname' => 'Aces',
                'league' => 'WNBA',
                'url' => 'https://aces.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661319/primary/L/logo.svg',
                'color' => '#000000',
            ],
            [
                'name' => 'Los Angeles Sparks',
                'abbreviation' => 'LA',
                'location' => 'Los Angeles',
                'nickname' => 'Sparks',
                'league' => 'WNBA',
                'url' => 'https://sparks.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661314/primary/L/logo.svg',
                'color' => '#702F8A',
            ],
            [
                'name' => 'Minnesota Lynx',
                'abbreviation' => 'MIN',
                'location' => 'Minnesota',
                'nickname' => 'Lynx',
                'league' => 'WNBA',
                'url' => 'https://lynx.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661315/primary/L/logo.svg',
                'color' => '#266092',
            ],
            [
                'name' => 'New York Liberty',
                'abbreviation' => 'NY',
                'location' => 'New York',
                'nickname' => 'Liberty',
                'league' => 'WNBA',
                'url' => 'https://liberty.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661312/primary/L/logo.svg',
                'color' => '#86CEBC',
            ],
            [
                'name' => 'Phoenix Mercury',
                'abbreviation' => 'PHX',
                'location' => 'Phoenix',
                'nickname' => 'Mercury',
                'league' => 'WNBA',
                'url' => 'https://mercury.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661318/primary/L/logo.svg',
                'color' => '#201747',
            ],
            [
                'name' => 'Seattle Storm',
                'abbreviation' => 'SEA',
                'location' => 'Seattle',
                'nickname' => 'Storm',
                'league' => 'WNBA',
                'url' => 'https://storm.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661316/primary/L/logo.svg',
                'color' => '#2C5234',
            ],
            [
                'name' => 'Washington Mystics',
                'abbreviation' => 'WAS',
                'location' => 'Washington',
                'nickname' => 'Mystics',
                'league' => 'WNBA',
                'url' => 'https://mystics.wnba.com/',
                'logo_url' => 'https://cdn.wnba.com/logos/wnba/1611661321/primary/L/logo.svg',
                'color' => '#002B5C',
            ],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
