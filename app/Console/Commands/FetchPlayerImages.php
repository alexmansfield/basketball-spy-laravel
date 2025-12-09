<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchPlayerImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:fetch-images {--force : Re-download images even if they exist locally}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download player headshot images from CDN and store them locally in Laravel storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏀 Starting player image download...');

        // Get all players with headshot URLs
        $players = Player::whereNotNull('headshot_url')->get();
        $total = $players->count();

        if ($total === 0) {
            $this->warn('No players found with headshot URLs.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} players with headshot URLs");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($players as $player) {
            $result = $this->downloadPlayerImage($player);

            if ($result === 'downloaded') {
                $downloaded++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Download complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Downloaded', $downloaded],
                ['Skipped (already exists)', $skipped],
                ['Failed', $failed],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Download a single player's headshot image
     */
    protected function downloadPlayerImage(Player $player): string
    {
        $force = $this->option('force');

        // Parse filename from URL (e.g., "201939.png" from the CDN URL)
        $filename = basename(parse_url($player->headshot_url, PHP_URL_PATH));
        $storagePath = "player-headshots/{$filename}";

        // Skip if already exists and not forcing re-download
        if (!$force && Storage::disk('public')->exists($storagePath)) {
            return 'skipped';
        }

        try {
            // Download the image
            $response = Http::timeout(10)->get($player->headshot_url);

            if (!$response->successful()) {
                $this->newLine();
                $this->error("Failed to download image for {$player->name}: HTTP {$response->status()}");
                return 'failed';
            }

            // Store the image
            Storage::disk('public')->put($storagePath, $response->body());

            // Update player record to use local path
            $player->update([
                'headshot_url' => Storage::url($storagePath)
            ]);

            return 'downloaded';

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("Exception downloading image for {$player->name}: {$e->getMessage()}");
            return 'failed';
        }
    }
}
