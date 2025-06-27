<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ReleaseExpiredRoomsCommand;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ReleaseExpiredRoomsCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('rooms:release-expired')->everyFiveMinutes(); // أو everyMinute للتجربة
    }
}
