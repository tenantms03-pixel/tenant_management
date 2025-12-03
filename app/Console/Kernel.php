<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // In app/Console/Kernel.php (TEMPORARY MODIFICATION)

    protected function schedule(Schedule $schedule): void
    {
        // Find the NEXT MINUTE (e.g., if it's 2:30, set it to 2:31)
        $nextMinute = now()->addMinute()->format('H:i'); 

        $schedule->command('update:tenant-status')
            ->dailyAt($nextMinute) // TEMPORARILY set to run at the next minute
            ->appendOutputTo(storage_path('logs/tenant_status.log'));
    }
    
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
