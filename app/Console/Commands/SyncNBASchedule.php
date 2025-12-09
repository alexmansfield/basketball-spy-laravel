<?php

namespace App\Console\Commands;

use App\Jobs\SyncNBASchedule as SyncNBAScheduleJob;
use Illuminate\Console\Command;

class SyncNBASchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-nba-schedule
                            {--days=7 : Number of days to fetch (default: 7)}
                            {--queue : Dispatch job to queue instead of running synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync NBA schedule from OpenAI with web search for the next N days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $useQueue = $this->option('queue');

        $this->info("Syncing NBA schedule for the next {$days} day(s)...");

        if ($useQueue) {
            SyncNBAScheduleJob::dispatch($days);
            $this->info('Job dispatched to queue. Run `php artisan queue:work` to process.');
        } else {
            $this->info('Running synchronously (this may take up to 90 seconds)...');

            try {
                $job = new SyncNBAScheduleJob($days);
                $job->handle();
                $this->info('Sync complete!');
            } catch (\Exception $e) {
                $this->error("Failed: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
