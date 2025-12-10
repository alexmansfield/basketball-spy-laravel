<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncHeadshotsFromFile extends Command
{
    protected $signature = 'app:sync-headshots';

    protected $description = 'Sync NBA player headshots from local JSON file (matches by name)';

    /**
     * Manual name mappings for players with different name formats.
     * Key: database name (normalized), Value: JSON file name (normalized)
     */
    protected array $nameAliases = [
        'nicolas claxton' => 'nic claxton',
        'cameron johnson' => 'cam johnson',
        'kenneth lofton' => 'kenneth lofton jr',
        'ej liddell' => 'ej liddell',
        'nigel hayes' => 'nigel hayes-davis',
        'bones hyland' => 'nahshon hyland',
        'nahshon hyland' => 'nahshon hyland',
    ];

    public function handle(): int
    {
        $this->info('Syncing NBA player headshots from JSON file...');

        $filePath = database_path('data/nba_player_headshots.json');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $json = file_get_contents($filePath);
        $headshots = json_decode($json, true);

        if (!is_array($headshots)) {
            $this->error('Invalid JSON format');
            return Command::FAILURE;
        }

        $this->info("Loaded " . count($headshots) . " players from JSON file");

        // Build lookups by normalized name and by last name for fuzzy matching
        $headshotsByName = [];
        $headshotsByLastName = [];
        foreach ($headshots as $player) {
            $normalizedName = $this->normalizeName($player['full_name']);
            $headshotsByName[$normalizedName] = [
                'nba_player_id' => $player['player_id'],
                'headshot_url' => $player['headshot_url'],
                'original_name' => $player['full_name'],
            ];

            // Also index by last name for fuzzy matching
            $lastName = $this->normalizeName($player['last_name']);
            if (!isset($headshotsByLastName[$lastName])) {
                $headshotsByLastName[$lastName] = [];
            }
            $headshotsByLastName[$lastName][] = $headshotsByName[$normalizedName];
        }

        // Get all active players from database
        $activePlayers = Player::where('is_active', true)->get();
        $this->info("Found " . $activePlayers->count() . " active players in database");

        $stats = ['matched' => 0, 'unmatched' => 0];
        $unmatched = [];

        $bar = $this->output->createProgressBar($activePlayers->count());
        $bar->start();

        foreach ($activePlayers as $player) {
            $normalizedName = $this->normalizeName($player->name);
            $data = null;

            // Try exact match first
            if (isset($headshotsByName[$normalizedName])) {
                $data = $headshotsByName[$normalizedName];
            }

            // Try alias mapping
            if (!$data && isset($this->nameAliases[$normalizedName])) {
                $aliasName = $this->normalizeName($this->nameAliases[$normalizedName]);
                if (isset($headshotsByName[$aliasName])) {
                    $data = $headshotsByName[$aliasName];
                }
            }

            // Try fuzzy match by last name + first initial
            if (!$data) {
                $parts = explode(' ', $normalizedName);
                if (count($parts) >= 2) {
                    $firstName = $parts[0];
                    $lastName = end($parts);
                    $firstInitial = substr($firstName, 0, 1);

                    if (isset($headshotsByLastName[$lastName])) {
                        foreach ($headshotsByLastName[$lastName] as $candidate) {
                            $candidateNormalized = $this->normalizeName($candidate['original_name']);
                            // Check if first initial matches
                            if (str_starts_with($candidateNormalized, $firstInitial)) {
                                $data = $candidate;
                                break;
                            }
                        }
                    }
                }
            }

            if ($data) {
                $player->update([
                    'name' => $data['original_name'], // Use proper accented name from NBA
                    'nba_player_id' => $data['nba_player_id'],
                    'headshot_url' => $data['headshot_url'],
                ]);
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
                $unmatched[] = $player->name;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Matched: {$stats['matched']} players");

        if ($stats['unmatched'] > 0) {
            $this->warn("Unmatched: {$stats['unmatched']} players");
            $this->line("Unmatched players:");
            foreach (array_slice($unmatched, 0, 30) as $name) {
                $this->line("  - {$name}");
            }
            if (count($unmatched) > 30) {
                $this->line("  ... and " . (count($unmatched) - 30) . " more");
            }
        }

        // Clear player cache
        $this->info('Clearing player cache...');
        Cache::flush();
        $this->info('Cache cleared');

        return Command::SUCCESS;
    }

    /**
     * Normalize a player name for matching.
     * Handles accented characters, suffixes, and common variations.
     */
    protected function normalizeName(string $name): string
    {
        $name = trim($name);

        // Manual transliteration map for common characters
        $translitMap = [
            // Serbian/Croatian
            'ć' => 'c', 'Ć' => 'C',
            'č' => 'c', 'Č' => 'C',
            'ž' => 'z', 'Ž' => 'Z',
            'š' => 's', 'Š' => 'S',
            'đ' => 'd', 'Đ' => 'D',
            // Latvian (Porzingis)
            'ņ' => 'n', 'Ņ' => 'N',
            'ģ' => 'g', 'Ģ' => 'G',
            'ķ' => 'k', 'Ķ' => 'K',
            'ļ' => 'l', 'Ļ' => 'L',
            // Lithuanian (Valanciunas)
            'ū' => 'u', 'Ū' => 'U',
            'ą' => 'a', 'Ą' => 'A',
            'ę' => 'e', 'Ę' => 'E',
            'ė' => 'e', 'Ė' => 'E',
            'į' => 'i', 'Į' => 'I',
            'ų' => 'u', 'Ų' => 'U',
            // Spanish
            'ñ' => 'n', 'Ñ' => 'N',
            // German/Nordic
            'ö' => 'o', 'Ö' => 'O',
            'ü' => 'u', 'Ü' => 'U',
            'ä' => 'a', 'Ä' => 'A',
            // French/Portuguese
            'é' => 'e', 'É' => 'E',
            'è' => 'e', 'È' => 'E',
            'ê' => 'e', 'Ê' => 'E',
            'ë' => 'e', 'Ë' => 'E',
            'à' => 'a', 'À' => 'A',
            'á' => 'a', 'Á' => 'A',
            'â' => 'a', 'Â' => 'A',
            'í' => 'i', 'Í' => 'I',
            'ì' => 'i', 'Ì' => 'I',
            'î' => 'i', 'Î' => 'I',
            'ï' => 'i', 'Ï' => 'I',
            'ó' => 'o', 'Ó' => 'O',
            'ò' => 'o', 'Ò' => 'O',
            'ô' => 'o', 'Ô' => 'O',
            'ú' => 'u', 'Ú' => 'U',
            'ù' => 'u', 'Ù' => 'U',
            'û' => 'u', 'Û' => 'U',
            'ý' => 'y', 'Ý' => 'Y',
            'ÿ' => 'y', 'Ÿ' => 'Y',
            // Typography
            "\u{2018}" => "'", "\u{2019}" => "'",
            "\u{2013}" => '-', "\u{2014}" => '-',
        ];

        $name = strtr($name, $translitMap);

        // Lowercase after transliteration
        $name = strtolower($name);

        // Remove suffixes like Jr., Sr., II, III, IV
        $name = preg_replace('/\s+(jr\.?|sr\.?|ii|iii|iv)$/i', '', $name);

        // Remove periods
        $name = str_replace('.', '', $name);

        // Normalize whitespace
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove any remaining non-ASCII characters
        $name = preg_replace('/[^\x20-\x7E]/', '', $name);

        return trim($name);
    }
}
