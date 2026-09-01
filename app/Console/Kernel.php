<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ClearCacheCommand;
use App\Console\Commands\ReleaseExpiredVenueSeatHolds;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ClearCacheCommand::class,
        ReleaseExpiredVenueSeatHolds::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('cron:clear-cache')
         ->everyFiveMinutes()
         ->appendOutputTo('/home/yawiszxr/tickets.theeventpalette.com/storage/logs/schedule.log');

        $schedule->command('venue-seats:release-expired-holds')->everyMinute();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
