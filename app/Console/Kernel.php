<?php

namespace App\Console;

use App\Console\Commands\DownloadAndImportDcadData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DownloadAndImportDcadData::class)
            ->dailyAt('05:00')
            ->days([Schedule::WEDNESDAY, Schedule::SATURDAY])
            ->sendOutputTo(storage_path('logs/dl-import-cmd-' . now()->format('Y-m-d') . '.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected function bootstrappers(): array
    {
        return array_merge(
            [\Bugsnag\BugsnagLaravel\OomBootstrapper::class],
            parent::bootstrappers(),
        );
    }
}
