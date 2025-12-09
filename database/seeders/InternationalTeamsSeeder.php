<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class InternationalTeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds major international basketball teams from EuroLeague, ACB (Spain),
     * Chinese Basketball Association, and other top leagues
     */
    public function run(): void
    {
        $teams = [
            // EuroLeague - Top European Teams
            [
                'name' => 'Real Madrid',
                'abbreviation' => 'RMB',
                'location' => 'Madrid',
                'nickname' => 'Real Madrid',
                'league' => 'Foreign',
                'url' => 'https://www.realmadrid.com/en/basketball',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/5/56/Real_Madrid_CF.svg',
                'color' => '#FEBE10',
            ],
            [
                'name' => 'FC Barcelona',
                'abbreviation' => 'FCB',
                'location' => 'Barcelona',
                'nickname' => 'Barça',
                'league' => 'Foreign',
                'url' => 'https://www.fcbarcelona.com/en/basketball',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/4/47/FC_Barcelona_%28crest%29.svg',
                'color' => '#A50044',
            ],
            [
                'name' => 'Panathinaikos Athens',
                'abbreviation' => 'PAO',
                'location' => 'Athens',
                'nickname' => 'Panathinaikos',
                'league' => 'Foreign',
                'url' => 'https://www.pao.gr/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/c/c3/Panathinaikos_BC_logo.svg',
                'color' => '#00A859',
            ],
            [
                'name' => 'Olympiacos Piraeus',
                'abbreviation' => 'OLY',
                'location' => 'Piraeus',
                'nickname' => 'Olympiacos',
                'league' => 'Foreign',
                'url' => 'https://www.olympiacosbc.gr/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/7/76/Olympiacos_BC_logo.svg',
                'color' => '#C8102E',
            ],
            [
                'name' => 'Fenerbahçe Istanbul',
                'abbreviation' => 'FEN',
                'location' => 'Istanbul',
                'nickname' => 'Fenerbahçe',
                'league' => 'Foreign',
                'url' => 'https://www.fenerbahce.org/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/8/86/Fenerbah%C3%A7e_Men%27s_Basketball_logo.svg',
                'color' => '#FFCC00',
            ],
            [
                'name' => 'Anadolu Efes Istanbul',
                'abbreviation' => 'EFS',
                'location' => 'Istanbul',
                'nickname' => 'Anadolu Efes',
                'league' => 'Foreign',
                'url' => 'https://www.anadoluefessk.org/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/a/ad/Anadolu_Efes_S.K._logo.svg',
                'color' => '#003087',
            ],
            [
                'name' => 'CSKA Moscow',
                'abbreviation' => 'CSK',
                'location' => 'Moscow',
                'nickname' => 'CSKA',
                'league' => 'Foreign',
                'url' => 'https://www.cskabasket.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/1/1c/PBC_CSKA_Moscow_logo.svg',
                'color' => '#C8102E',
            ],
            [
                'name' => 'Maccabi Tel Aviv',
                'abbreviation' => 'MTA',
                'location' => 'Tel Aviv',
                'nickname' => 'Maccabi',
                'league' => 'Foreign',
                'url' => 'https://www.maccabi.co.il/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/c/c4/Maccabi_Tel_Aviv_B.C._logo.svg',
                'color' => '#FFDD00',
            ],
            [
                'name' => 'Zalgiris Kaunas',
                'abbreviation' => 'ZAL',
                'location' => 'Kaunas',
                'nickname' => 'Zalgiris',
                'league' => 'Foreign',
                'url' => 'https://www.zalgiris.lt/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/f/fb/BC_%C5%BDalgiris_logo.svg',
                'color' => '#008542',
            ],
            [
                'name' => 'Bayern Munich',
                'abbreviation' => 'BAY',
                'location' => 'Munich',
                'nickname' => 'Bayern',
                'league' => 'Foreign',
                'url' => 'https://fcbayern.com/en/basketball',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1b/FC_Bayern_M%C3%BCnchen_logo_%282017%29.svg',
                'color' => '#DC052D',
            ],

            // Chinese Basketball Association - Top Teams
            [
                'name' => 'Guangdong Southern Tigers',
                'abbreviation' => 'GDT',
                'location' => 'Guangdong',
                'nickname' => 'Southern Tigers',
                'league' => 'Foreign',
                'url' => 'http://www.guangdongsoutherntigers.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/4/49/Guangdong_Southern_Tigers_logo.png',
                'color' => '#C8102E',
            ],
            [
                'name' => 'Beijing Ducks',
                'abbreviation' => 'BJD',
                'location' => 'Beijing',
                'nickname' => 'Ducks',
                'league' => 'Foreign',
                'url' => 'http://www.bjducks.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/d/d4/Beijing_Ducks_logo.png',
                'color' => '#003087',
            ],
            [
                'name' => 'Liaoning Flying Leopards',
                'abbreviation' => 'LFL',
                'location' => 'Liaoning',
                'nickname' => 'Flying Leopards',
                'league' => 'Foreign',
                'url' => 'http://www.lnflyingleopards.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/a/a8/Liaoning_Flying_Leopards_logo.png',
                'color' => '#FFC72C',
            ],

            // Australian NBL - Top Teams
            [
                'name' => 'Sydney Kings',
                'abbreviation' => 'SYK',
                'location' => 'Sydney',
                'nickname' => 'Kings',
                'league' => 'Foreign',
                'url' => 'https://www.sydneykings.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/5/57/Sydney_Kings_logo.svg',
                'color' => '#552583',
            ],
            [
                'name' => 'Melbourne United',
                'abbreviation' => 'MEL',
                'location' => 'Melbourne',
                'nickname' => 'United',
                'league' => 'Foreign',
                'url' => 'https://www.melbourneunited.com.au/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/e/e6/Melbourne_United_logo.svg',
                'color' => '#003087',
            ],

            // Additional Top International Teams
            [
                'name' => 'Baskonia Vitoria-Gasteiz',
                'abbreviation' => 'BAS',
                'location' => 'Vitoria-Gasteiz',
                'nickname' => 'Baskonia',
                'league' => 'Foreign',
                'url' => 'https://www.baskonia.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/0/0f/Saski_Baskonia_logo.svg',
                'color' => '#005EB8',
            ],
            [
                'name' => 'Virtus Bologna',
                'abbreviation' => 'VIR',
                'location' => 'Bologna',
                'nickname' => 'Virtus',
                'league' => 'Foreign',
                'url' => 'https://www.virtus.it/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/a/a4/Virtus_Bologna_logo.svg',
                'color' => '#000000',
            ],
            [
                'name' => 'AS Monaco',
                'abbreviation' => 'ASM',
                'location' => 'Monaco',
                'nickname' => 'Monaco',
                'league' => 'Foreign',
                'url' => 'https://www.asmonaco-basket.com/',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/5/51/AS_Monaco_Basket_logo.svg',
                'color' => '#C8102E',
            ],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
