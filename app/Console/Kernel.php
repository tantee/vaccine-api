<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Commands\PassportPersonalClientCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        $schedule->call(function() {
            return \App\Http\Controllers\Encounter\OPDController::autoCloseEncounter();
        })->daily()
            ->name('AutoCloseEncounterOPD')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Covid19\VaccineController::autoDischarge();
        })->everyTenMinutes()
            ->name('Covid19VaccienAutoDischarge')
            ->onOneServer();
            
        $schedule->call(function() {
            return \App\Http\Controllers\Export\MOPHExportController::sendUpdateImmunizationData(false);
        })->everyFifteenMinutes()
            ->name('MOPHSendUpdateImmunizationData')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Export\MOPHExportController::sendUpdateImmunizationData(true);
        })->dailyAt('00:00')
            ->name('MOPHSendUpdateImmunizationDataForce')
            ->onOneServer();
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
