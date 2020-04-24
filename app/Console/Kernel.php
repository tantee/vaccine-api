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
            return \App\Http\Controllers\Export\ExportController::Export();
        })->everyFiveMinutes()
            ->name('ExportToAccounting')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Export\EclaimController::ExportUcsOpd();
        })->dailyAt('01:00')
            ->name('ExportToEclaim16FolderDb')
            ->onOneServer();
        
        $schedule->call(function() {
            return \App\Http\Controllers\Import\PacsImportController::Import();
        })->everyMinute()
            ->name('ImportPacsStudy')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Encounter\IPDController::autoCharge();
        })->everyFiveMinutes()
            ->name('AutoChargeIPD')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Encounter\OPDController::autoCloseEncounter();
        })->daily()
            ->name('AutoCloseEncounterOPD')
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
