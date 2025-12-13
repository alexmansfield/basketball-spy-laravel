<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class NBAArenaSeeder extends Seeder
{
    public function run(): void
    {
        $arenas = [
            'ATL' => ['State Farm Arena', 'Atlanta', 'GA', 33.7573, -84.3963],
            'BOS' => ['TD Garden', 'Boston', 'MA', 42.3662, -71.0621],
            'BKN' => ['Barclays Center', 'Brooklyn', 'NY', 40.6826, -73.9754],
            'CHA' => ['Spectrum Center', 'Charlotte', 'NC', 35.2251, -80.8392],
            'CHI' => ['United Center', 'Chicago', 'IL', 41.8807, -87.6742],
            'CLE' => ['Rocket Mortgage FieldHouse', 'Cleveland', 'OH', 41.4965, -81.6882],
            'DAL' => ['American Airlines Center', 'Dallas', 'TX', 32.7905, -96.8103],
            'DEN' => ['Ball Arena', 'Denver', 'CO', 39.7487, -105.0077],
            'DET' => ['Little Caesars Arena', 'Detroit', 'MI', 42.3411, -83.0552],
            'GSW' => ['Chase Center', 'San Francisco', 'CA', 37.7680, -122.3877],
            'HOU' => ['Toyota Center', 'Houston', 'TX', 29.7508, -95.3621],
            'IND' => ['Gainbridge Fieldhouse', 'Indianapolis', 'IN', 39.7640, -86.1555],
            'LAC' => ['Intuit Dome', 'Inglewood', 'CA', 33.9425, -118.3419],
            'LAL' => ['Crypto.com Arena', 'Los Angeles', 'CA', 34.0430, -118.2673],
            'MEM' => ['FedExForum', 'Memphis', 'TN', 35.1382, -90.0506],
            'MIA' => ['Kaseya Center', 'Miami', 'FL', 25.7814, -80.1870],
            'MIL' => ['Fiserv Forum', 'Milwaukee', 'WI', 43.0451, -87.9173],
            'MIN' => ['Target Center', 'Minneapolis', 'MN', 44.9795, -93.2761],
            'NOP' => ['Smoothie King Center', 'New Orleans', 'LA', 29.9490, -90.0821],
            'NYK' => ['Madison Square Garden', 'New York', 'NY', 40.7505, -73.9934],
            'OKC' => ['Paycom Center', 'Oklahoma City', 'OK', 35.4634, -97.5151],
            'ORL' => ['Kia Center', 'Orlando', 'FL', 28.5392, -81.3839],
            'PHI' => ['Wells Fargo Center', 'Philadelphia', 'PA', 39.9012, -75.1720],
            'PHX' => ['Footprint Center', 'Phoenix', 'AZ', 33.4457, -112.0712],
            'POR' => ['Moda Center', 'Portland', 'OR', 45.5316, -122.6668],
            'SAC' => ['Golden 1 Center', 'Sacramento', 'CA', 38.5802, -121.4997],
            'SAS' => ['Frost Bank Center', 'San Antonio', 'TX', 29.4270, -98.4375],
            'TOR' => ['Scotiabank Arena', 'Toronto', 'ON', 43.6435, -79.3791],
            'UTA' => ['Delta Center', 'Salt Lake City', 'UT', 40.7683, -111.9011],
            'WAS' => ['Capital One Arena', 'Washington', 'DC', 38.8981, -77.0209],
        ];

        foreach ($arenas as $abbr => [$name, $city, $state, $lat, $lng]) {
            Team::where('abbreviation', $abbr)->update([
                'arena_name' => $name,
                'arena_city' => $city,
                'arena_state' => $state,
                'arena_latitude' => $lat,
                'arena_longitude' => $lng,
            ]);
        }

        $this->command->info('Updated ' . count($arenas) . ' NBA team arenas');
    }
}
