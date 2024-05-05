<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SyncTickets::class,
        Commands\SyncEbayTickets::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('minute:syncTickets')
                 ->everyFiveMinutes()
                 ->timezone('Australia/Sydney');

        $schedule->command('minute:syncEbayTickets')
                 ->everyTenMinutes()
                 ->timezone('Australia/Sydney');

        $schedule->command('minute:checkAwaitingFulfillment')
                 ->everyFifteenMinutes()
                 ->timezone('Australia/Sydney');

        $schedule->command('minute:checkAwaitingShipment')
                 ->dailyAt('08:00')
                 ->timezone('Australia/Sydney');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
