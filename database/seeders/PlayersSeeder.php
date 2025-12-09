<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class PlayersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds key players for all 30 NBA teams using official NBA CDN headshots
     */
    public function run(): void
    {
        $this->seedEasternConference();
        $this->seedWesternConference();
    }

    protected function seedEasternConference(): void
    {
        // Atlanta Hawks
        $this->seedTeam('ATL', [
            ['name' => 'Trae Young', 'jersey' => '11', 'position' => 'G', 'height' => '6\'1"', 'weight' => '164 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629027.png'],
            ['name' => 'Dejounte Murray', 'jersey' => '5', 'position' => 'G', 'height' => '6\'4"', 'weight' => '180 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627749.png'],
            ['name' => 'Clint Capela', 'jersey' => '15', 'position' => 'C', 'height' => '6\'10"', 'weight' => '256 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203991.png'],
            ['name' => 'De\'Andre Hunter', 'jersey' => '12', 'position' => 'F', 'height' => '6\'8"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629631.png'],
            ['name' => 'Bogdan Bogdanovic', 'jersey' => '13', 'position' => 'G', 'height' => '6\'5"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203992.png'],
        ]);

        // Boston Celtics
        $this->seedTeam('BOS', [
            ['name' => 'Jayson Tatum', 'jersey' => '0', 'position' => 'F', 'height' => '6\'8"', 'weight' => '210 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628369.png'],
            ['name' => 'Jaylen Brown', 'jersey' => '7', 'position' => 'G', 'height' => '6\'6"', 'weight' => '223 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627759.png'],
            ['name' => 'Kristaps Porzingis', 'jersey' => '8', 'position' => 'F', 'height' => '7\'2"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/204001.png'],
            ['name' => 'Derrick White', 'jersey' => '9', 'position' => 'G', 'height' => '6\'4"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628401.png'],
            ['name' => 'Jrue Holiday', 'jersey' => '4', 'position' => 'G', 'height' => '6\'3"', 'weight' => '205 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201950.png'],
        ]);

        // Brooklyn Nets
        $this->seedTeam('BKN', [
            ['name' => 'Mikal Bridges', 'jersey' => '1', 'position' => 'F', 'height' => '6\'6"', 'weight' => '209 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628969.png'],
            ['name' => 'Cam Thomas', 'jersey' => '24', 'position' => 'G', 'height' => '6\'3"', 'weight' => '210 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630560.png'],
            ['name' => 'Nic Claxton', 'jersey' => '33', 'position' => 'C', 'height' => '6\'11"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629651.png'],
            ['name' => 'Ben Simmons', 'jersey' => '10', 'position' => 'G', 'height' => '6\'10"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627732.png'],
            ['name' => 'Spencer Dinwiddie', 'jersey' => '26', 'position' => 'G', 'height' => '6\'5"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203915.png'],
        ]);

        // Charlotte Hornets
        $this->seedTeam('CHA', [
            ['name' => 'LaMelo Ball', 'jersey' => '1', 'position' => 'G', 'height' => '6\'6"', 'weight' => '180 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630163.png'],
            ['name' => 'Miles Bridges', 'jersey' => '0', 'position' => 'F', 'height' => '6\'6"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628970.png'],
            ['name' => 'Brandon Miller', 'jersey' => '24', 'position' => 'F', 'height' => '6\'9"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641705.png'],
            ['name' => 'Mark Williams', 'jersey' => '5', 'position' => 'C', 'height' => '7\'0"', 'weight' => '242 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631102.png'],
            ['name' => 'Terry Rozier', 'jersey' => '3', 'position' => 'G', 'height' => '6\'1"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626179.png'],
        ]);

        // Chicago Bulls
        $this->seedTeam('CHI', [
            ['name' => 'Zach LaVine', 'jersey' => '8', 'position' => 'G', 'height' => '6\'5"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203897.png'],
            ['name' => 'DeMar DeRozan', 'jersey' => '11', 'position' => 'F', 'height' => '6\'6"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201942.png'],
            ['name' => 'Nikola Vucevic', 'jersey' => '9', 'position' => 'C', 'height' => '6\'10"', 'weight' => '260 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202696.png'],
            ['name' => 'Coby White', 'jersey' => '0', 'position' => 'G', 'height' => '6\'5"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629632.png'],
            ['name' => 'Patrick Williams', 'jersey' => '44', 'position' => 'F', 'height' => '6\'7"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630167.png'],
        ]);

        // Cleveland Cavaliers
        $this->seedTeam('CLE', [
            ['name' => 'Donovan Mitchell', 'jersey' => '45', 'position' => 'G', 'height' => '6\'1"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628378.png'],
            ['name' => 'Darius Garland', 'jersey' => '10', 'position' => 'G', 'height' => '6\'1"', 'weight' => '192 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629636.png'],
            ['name' => 'Evan Mobley', 'jersey' => '4', 'position' => 'F', 'height' => '7\'0"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630596.png'],
            ['name' => 'Jarrett Allen', 'jersey' => '31', 'position' => 'C', 'height' => '6\'11"', 'weight' => '243 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628386.png'],
            ['name' => 'Caris LeVert', 'jersey' => '3', 'position' => 'G', 'height' => '6\'6"', 'weight' => '205 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627747.png'],
        ]);

        // Detroit Pistons
        $this->seedTeam('DET', [
            ['name' => 'Cade Cunningham', 'jersey' => '2', 'position' => 'G', 'height' => '6\'6"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630595.png'],
            ['name' => 'Jaden Ivey', 'jersey' => '23', 'position' => 'G', 'height' => '6\'4"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631093.png'],
            ['name' => 'Jalen Duren', 'jersey' => '0', 'position' => 'C', 'height' => '6\'10"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631105.png'],
            ['name' => 'Bojan Bogdanovic', 'jersey' => '44', 'position' => 'F', 'height' => '6\'7"', 'weight' => '226 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202711.png'],
            ['name' => 'Ausar Thompson', 'jersey' => '9', 'position' => 'G', 'height' => '6\'6"', 'weight' => '205 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641712.png'],
        ]);

        // Indiana Pacers
        $this->seedTeam('IND', [
            ['name' => 'Tyrese Haliburton', 'jersey' => '0', 'position' => 'G', 'height' => '6\'5"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630169.png'],
            ['name' => 'Pascal Siakam', 'jersey' => '43', 'position' => 'F', 'height' => '6\'9"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627783.png'],
            ['name' => 'Myles Turner', 'jersey' => '33', 'position' => 'C', 'height' => '6\'11"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626167.png'],
            ['name' => 'Bennedict Mathurin', 'jersey' => '00', 'position' => 'G', 'height' => '6\'6"', 'weight' => '210 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631096.png'],
            ['name' => 'Aaron Nesmith', 'jersey' => '23', 'position' => 'F', 'height' => '6\'6"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630174.png'],
        ]);

        // Miami Heat
        $this->seedTeam('MIA', [
            ['name' => 'Jimmy Butler', 'jersey' => '22', 'position' => 'F', 'height' => '6\'7"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202710.png'],
            ['name' => 'Bam Adebayo', 'jersey' => '13', 'position' => 'C', 'height' => '6\'9"', 'weight' => '255 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628389.png'],
            ['name' => 'Tyler Herro', 'jersey' => '14', 'position' => 'G', 'height' => '6\'5"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629639.png'],
            ['name' => 'Kyle Lowry', 'jersey' => '7', 'position' => 'G', 'height' => '6\'0"', 'weight' => '196 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/200768.png'],
            ['name' => 'Jaime Jaquez Jr.', 'jersey' => '11', 'position' => 'G', 'height' => '6\'6"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641743.png'],
        ]);

        // Milwaukee Bucks
        $this->seedTeam('MIL', [
            ['name' => 'Giannis Antetokounmpo', 'jersey' => '34', 'position' => 'F', 'height' => '6\'11"', 'weight' => '242 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203507.png'],
            ['name' => 'Damian Lillard', 'jersey' => '0', 'position' => 'G', 'height' => '6\'2"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203081.png'],
            ['name' => 'Khris Middleton', 'jersey' => '22', 'position' => 'F', 'height' => '6\'7"', 'weight' => '222 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203114.png'],
            ['name' => 'Brook Lopez', 'jersey' => '11', 'position' => 'C', 'height' => '7\'0"', 'weight' => '282 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201572.png'],
            ['name' => 'Bobby Portis', 'jersey' => '9', 'position' => 'F', 'height' => '6\'10"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626171.png'],
        ]);

        // New York Knicks
        $this->seedTeam('NYK', [
            ['name' => 'Jalen Brunson', 'jersey' => '11', 'position' => 'G', 'height' => '6\'2"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628973.png'],
            ['name' => 'Julius Randle', 'jersey' => '30', 'position' => 'F', 'height' => '6\'8"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203944.png'],
            ['name' => 'RJ Barrett', 'jersey' => '9', 'position' => 'G', 'height' => '6\'6"', 'weight' => '214 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629628.png'],
            ['name' => 'Mitchell Robinson', 'jersey' => '23', 'position' => 'C', 'height' => '7\'0"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629011.png'],
            ['name' => 'Immanuel Quickley', 'jersey' => '5', 'position' => 'G', 'height' => '6\'3"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630193.png'],
        ]);

        // Orlando Magic
        $this->seedTeam('ORL', [
            ['name' => 'Paolo Banchero', 'jersey' => '5', 'position' => 'F', 'height' => '6\'10"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631094.png'],
            ['name' => 'Franz Wagner', 'jersey' => '22', 'position' => 'F', 'height' => '6\'10"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630533.png'],
            ['name' => 'Wendell Carter Jr.', 'jersey' => '34', 'position' => 'C', 'height' => '6\'10"', 'weight' => '270 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628976.png'],
            ['name' => 'Markelle Fultz', 'jersey' => '20', 'position' => 'G', 'height' => '6\'4"', 'weight' => '209 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628365.png'],
            ['name' => 'Cole Anthony', 'jersey' => '50', 'position' => 'G', 'height' => '6\'2"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630175.png'],
        ]);

        // Philadelphia 76ers
        $this->seedTeam('PHI', [
            ['name' => 'Joel Embiid', 'jersey' => '21', 'position' => 'C', 'height' => '7\'0"', 'weight' => '280 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203954.png'],
            ['name' => 'Tyrese Maxey', 'jersey' => '0', 'position' => 'G', 'height' => '6\'2"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630178.png'],
            ['name' => 'Tobias Harris', 'jersey' => '12', 'position' => 'F', 'height' => '6\'7"', 'weight' => '226 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202699.png'],
            ['name' => 'De\'Anthony Melton', 'jersey' => '8', 'position' => 'G', 'height' => '6\'2"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629001.png'],
            ['name' => 'Kelly Oubre Jr.', 'jersey' => '9', 'position' => 'F', 'height' => '6\'7"', 'weight' => '203 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626162.png'],
        ]);

        // Toronto Raptors
        $this->seedTeam('TOR', [
            ['name' => 'Scottie Barnes', 'jersey' => '4', 'position' => 'F', 'height' => '6\'7"', 'weight' => '227 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630567.png'],
            ['name' => 'Pascal Siakam', 'jersey' => '43', 'position' => 'F', 'height' => '6\'9"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627783.png'],
            ['name' => 'OG Anunoby', 'jersey' => '3', 'position' => 'F', 'height' => '6\'7"', 'weight' => '232 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628384.png'],
            ['name' => 'Dennis Schroder', 'jersey' => '17', 'position' => 'G', 'height' => '6\'1"', 'weight' => '172 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203471.png'],
            ['name' => 'Jakob Poeltl', 'jersey' => '19', 'position' => 'C', 'height' => '7\'1"', 'weight' => '245 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627751.png'],
        ]);

        // Washington Wizards
        $this->seedTeam('WAS', [
            ['name' => 'Kyle Kuzma', 'jersey' => '33', 'position' => 'F', 'height' => '6\'9"', 'weight' => '221 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628398.png'],
            ['name' => 'Jordan Poole', 'jersey' => '13', 'position' => 'G', 'height' => '6\'4"', 'weight' => '194 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629673.png'],
            ['name' => 'Tyus Jones', 'jersey' => '5', 'position' => 'G', 'height' => '6\'1"', 'weight' => '196 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626145.png'],
            ['name' => 'Daniel Gafford', 'jersey' => '21', 'position' => 'C', 'height' => '6\'10"', 'weight' => '234 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629655.png'],
            ['name' => 'Bilal Coulibaly', 'jersey' => '0', 'position' => 'G', 'height' => '6\'6"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641731.png'],
        ]);
    }

    protected function seedWesternConference(): void
    {
        // Dallas Mavericks
        $this->seedTeam('DAL', [
            ['name' => 'Luka Doncic', 'jersey' => '77', 'position' => 'F', 'height' => '6\'7"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629029.png'],
            ['name' => 'Kyrie Irving', 'jersey' => '2', 'position' => 'G', 'height' => '6\'2"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202681.png'],
            ['name' => 'Dereck Lively II', 'jersey' => '2', 'position' => 'C', 'height' => '7\'1"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641714.png'],
            ['name' => 'Tim Hardaway Jr.', 'jersey' => '10', 'position' => 'G', 'height' => '6\'5"', 'weight' => '205 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203501.png'],
            ['name' => 'Josh Green', 'jersey' => '8', 'position' => 'G', 'height' => '6\'6"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630182.png'],
        ]);

        // Denver Nuggets
        $this->seedTeam('DEN', [
            ['name' => 'Nikola Jokic', 'jersey' => '15', 'position' => 'C', 'height' => '6\'11"', 'weight' => '284 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203999.png'],
            ['name' => 'Jamal Murray', 'jersey' => '27', 'position' => 'G', 'height' => '6\'4"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627750.png'],
            ['name' => 'Michael Porter Jr.', 'jersey' => '1', 'position' => 'F', 'height' => '6\'10"', 'weight' => '218 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629008.png'],
            ['name' => 'Aaron Gordon', 'jersey' => '50', 'position' => 'F', 'height' => '6\'8"', 'weight' => '235 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203932.png'],
            ['name' => 'Kentavious Caldwell-Pope', 'jersey' => '5', 'position' => 'G', 'height' => '6\'5"', 'weight' => '204 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203484.png'],
        ]);

        // Golden State Warriors
        $this->seedTeam('GSW', [
            ['name' => 'Stephen Curry', 'jersey' => '30', 'position' => 'G', 'height' => '6\'2"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201939.png'],
            ['name' => 'Klay Thompson', 'jersey' => '11', 'position' => 'G', 'height' => '6\'6"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202691.png'],
            ['name' => 'Draymond Green', 'jersey' => '23', 'position' => 'F', 'height' => '6\'6"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203110.png'],
            ['name' => 'Andrew Wiggins', 'jersey' => '22', 'position' => 'F', 'height' => '6\'7"', 'weight' => '197 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203952.png'],
            ['name' => 'Jonathan Kuminga', 'jersey' => '00', 'position' => 'F', 'height' => '6\'7"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630228.png'],
        ]);

        // Houston Rockets
        $this->seedTeam('HOU', [
            ['name' => 'Jalen Green', 'jersey' => '4', 'position' => 'G', 'height' => '6\'4"', 'weight' => '186 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630224.png'],
            ['name' => 'Alperen Sengun', 'jersey' => '28', 'position' => 'C', 'height' => '6\'10"', 'weight' => '243 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630578.png'],
            ['name' => 'Jabari Smith Jr.', 'jersey' => '1', 'position' => 'F', 'height' => '6\'10"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631095.png'],
            ['name' => 'Fred VanVleet', 'jersey' => '5', 'position' => 'G', 'height' => '6\'1"', 'weight' => '197 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627832.png'],
            ['name' => 'Dillon Brooks', 'jersey' => '9', 'position' => 'F', 'height' => '6\'7"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628415.png'],
        ]);

        // LA Clippers
        $this->seedTeam('LAC', [
            ['name' => 'Kawhi Leonard', 'jersey' => '2', 'position' => 'F', 'height' => '6\'7"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202695.png'],
            ['name' => 'Paul George', 'jersey' => '13', 'position' => 'F', 'height' => '6\'8"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202331.png'],
            ['name' => 'James Harden', 'jersey' => '1', 'position' => 'G', 'height' => '6\'5"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201935.png'],
            ['name' => 'Russell Westbrook', 'jersey' => '0', 'position' => 'G', 'height' => '6\'3"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201566.png'],
            ['name' => 'Ivica Zubac', 'jersey' => '40', 'position' => 'C', 'height' => '7\'0"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627826.png'],
        ]);

        // LA Lakers
        $this->seedTeam('LAL', [
            ['name' => 'LeBron James', 'jersey' => '23', 'position' => 'F', 'height' => '6\'9"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/2544.png'],
            ['name' => 'Anthony Davis', 'jersey' => '3', 'position' => 'F', 'height' => '6\'10"', 'weight' => '253 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203076.png'],
            ['name' => 'D\'Angelo Russell', 'jersey' => '1', 'position' => 'G', 'height' => '6\'4"', 'weight' => '193 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626156.png'],
            ['name' => 'Austin Reaves', 'jersey' => '15', 'position' => 'G', 'height' => '6\'5"', 'weight' => '206 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630559.png'],
            ['name' => 'Rui Hachimura', 'jersey' => '28', 'position' => 'F', 'height' => '6\'8"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629060.png'],
        ]);

        // Memphis Grizzlies
        $this->seedTeam('MEM', [
            ['name' => 'Ja Morant', 'jersey' => '12', 'position' => 'G', 'height' => '6\'3"', 'weight' => '174 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629630.png'],
            ['name' => 'Jaren Jackson Jr.', 'jersey' => '13', 'position' => 'F', 'height' => '6\'11"', 'weight' => '242 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628991.png'],
            ['name' => 'Desmond Bane', 'jersey' => '22', 'position' => 'G', 'height' => '6\'5"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630217.png'],
            ['name' => 'Marcus Smart', 'jersey' => '36', 'position' => 'G', 'height' => '6\'4"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203935.png'],
            ['name' => 'Brandon Clarke', 'jersey' => '15', 'position' => 'F', 'height' => '6\'8"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629634.png'],
        ]);

        // Minnesota Timberwolves
        $this->seedTeam('MIN', [
            ['name' => 'Anthony Edwards', 'jersey' => '5', 'position' => 'G', 'height' => '6\'4"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630162.png'],
            ['name' => 'Karl-Anthony Towns', 'jersey' => '32', 'position' => 'C', 'height' => '7\'0"', 'weight' => '248 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626157.png'],
            ['name' => 'Rudy Gobert', 'jersey' => '27', 'position' => 'C', 'height' => '7\'1"', 'weight' => '258 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203497.png'],
            ['name' => 'Mike Conley', 'jersey' => '10', 'position' => 'G', 'height' => '6\'1"', 'weight' => '175 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201144.png'],
            ['name' => 'Jaden McDaniels', 'jersey' => '3', 'position' => 'F', 'height' => '6\'9"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630183.png'],
        ]);

        // New Orleans Pelicans
        $this->seedTeam('NOP', [
            ['name' => 'Zion Williamson', 'jersey' => '1', 'position' => 'F', 'height' => '6\'6"', 'weight' => '284 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629627.png'],
            ['name' => 'Brandon Ingram', 'jersey' => '14', 'position' => 'F', 'height' => '6\'8"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627742.png'],
            ['name' => 'CJ McCollum', 'jersey' => '3', 'position' => 'G', 'height' => '6\'3"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203468.png'],
            ['name' => 'Jonas Valanciunas', 'jersey' => '17', 'position' => 'C', 'height' => '6\'11"', 'weight' => '265 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/202685.png'],
            ['name' => 'Herbert Jones', 'jersey' => '5', 'position' => 'F', 'height' => '6\'7"', 'weight' => '206 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630529.png'],
        ]);

        // Oklahoma City Thunder
        $this->seedTeam('OKC', [
            ['name' => 'Shai Gilgeous-Alexander', 'jersey' => '2', 'position' => 'G', 'height' => '6\'6"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628983.png'],
            ['name' => 'Chet Holmgren', 'jersey' => '7', 'position' => 'C', 'height' => '7\'0"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631096.png'],
            ['name' => 'Jalen Williams', 'jersey' => '8', 'position' => 'F', 'height' => '6\'6"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631116.png'],
            ['name' => 'Josh Giddey', 'jersey' => '3', 'position' => 'G', 'height' => '6\'8"', 'weight' => '205 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630581.png'],
            ['name' => 'Luguentz Dort', 'jersey' => '5', 'position' => 'G', 'height' => '6\'3"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629652.png'],
        ]);

        // Phoenix Suns
        $this->seedTeam('PHX', [
            ['name' => 'Kevin Durant', 'jersey' => '35', 'position' => 'F', 'height' => '6\'10"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/201142.png'],
            ['name' => 'Devin Booker', 'jersey' => '1', 'position' => 'G', 'height' => '6\'5"', 'weight' => '206 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1626164.png'],
            ['name' => 'Bradley Beal', 'jersey' => '3', 'position' => 'G', 'height' => '6\'4"', 'weight' => '207 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203078.png'],
            ['name' => 'Jusuf Nurkic', 'jersey' => '20', 'position' => 'C', 'height' => '7\'0"', 'weight' => '290 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203994.png'],
            ['name' => 'Grayson Allen', 'jersey' => '8', 'position' => 'G', 'height' => '6\'4"', 'weight' => '198 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628960.png'],
        ]);

        // Portland Trail Blazers
        $this->seedTeam('POR', [
            ['name' => 'Damian Lillard', 'jersey' => '0', 'position' => 'G', 'height' => '6\'2"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203081.png'],
            ['name' => 'Anfernee Simons', 'jersey' => '1', 'position' => 'G', 'height' => '6\'3"', 'weight' => '181 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629014.png'],
            ['name' => 'Jerami Grant', 'jersey' => '9', 'position' => 'F', 'height' => '6\'8"', 'weight' => '210 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203924.png'],
            ['name' => 'Deandre Ayton', 'jersey' => '2', 'position' => 'C', 'height' => '7\'0"', 'weight' => '250 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629028.png'],
            ['name' => 'Scoot Henderson', 'jersey' => '00', 'position' => 'G', 'height' => '6\'2"', 'weight' => '195 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641706.png'],
        ]);

        // Sacramento Kings
        $this->seedTeam('SAC', [
            ['name' => 'De\'Aaron Fox', 'jersey' => '5', 'position' => 'G', 'height' => '6\'3"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628368.png'],
            ['name' => 'Domantas Sabonis', 'jersey' => '10', 'position' => 'F', 'height' => '6\'11"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1627734.png'],
            ['name' => 'Kevin Huerter', 'jersey' => '9', 'position' => 'G', 'height' => '6\'7"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628989.png'],
            ['name' => 'Harrison Barnes', 'jersey' => '40', 'position' => 'F', 'height' => '6\'8"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203084.png'],
            ['name' => 'Keegan Murray', 'jersey' => '13', 'position' => 'F', 'height' => '6\'8"', 'weight' => '225 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631097.png'],
        ]);

        // San Antonio Spurs
        $this->seedTeam('SAS', [
            ['name' => 'Victor Wembanyama', 'jersey' => '1', 'position' => 'C', 'height' => '7\'4"', 'weight' => '235 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641705.png'],
            ['name' => 'Keldon Johnson', 'jersey' => '3', 'position' => 'F', 'height' => '6\'5"', 'weight' => '220 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629640.png'],
            ['name' => 'Devin Vassell', 'jersey' => '24', 'position' => 'G', 'height' => '6\'5"', 'weight' => '200 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630170.png'],
            ['name' => 'Jeremy Sochan', 'jersey' => '10', 'position' => 'F', 'height' => '6\'9"', 'weight' => '230 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631110.png'],
            ['name' => 'Tre Jones', 'jersey' => '33', 'position' => 'G', 'height' => '6\'1"', 'weight' => '185 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1630200.png'],
        ]);

        // Utah Jazz
        $this->seedTeam('UTA', [
            ['name' => 'Lauri Markkanen', 'jersey' => '23', 'position' => 'F', 'height' => '7\'0"', 'weight' => '240 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1628374.png'],
            ['name' => 'Jordan Clarkson', 'jersey' => '00', 'position' => 'G', 'height' => '6\'4"', 'weight' => '194 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/203903.png'],
            ['name' => 'Collin Sexton', 'jersey' => '2', 'position' => 'G', 'height' => '6\'1"', 'weight' => '190 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1629012.png'],
            ['name' => 'Walker Kessler', 'jersey' => '24', 'position' => 'C', 'height' => '7\'0"', 'weight' => '245 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1631098.png'],
            ['name' => 'Taylor Hendricks', 'jersey' => '0', 'position' => 'F', 'height' => '6\'9"', 'weight' => '215 lbs', 'headshot_url' => 'https://cdn.nba.com/headshots/nba/latest/260x190/1641711.png'],
        ]);
    }

    /**
     * Helper to seed players for a specific team by abbreviation
     */
    protected function seedTeam(string $abbreviation, array $players): void
    {
        $team = Team::where('abbreviation', $abbreviation)->first();

        if ($team) {
            foreach ($players as $player) {
                Player::create(array_merge(['team_id' => $team->id], $player));
            }
        }
    }
}
