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
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        $schedule->call(function() {
            return \App\Http\Controllers\Patient\PatientController::updateNamePrefix();
        })->dailyAt('00:00')
            ->name('UpdateNamePrefix')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Export\MOPHExportController::sendBatchUpdateData();
        })->dailyAt('23:00')
            ->name('MOPHSendBatchUpdateData')
            ->onOneServer();

        $schedule->call(function() {
            return \App\Http\Controllers\Covid19\VaccineController::mophVipsCleanup();
        })->dailyAt('22:00')
            ->name('MOPHVipsCleanup')
            ->onOneServer();

        // $schedule->call(function() {
        //     return \App\Http\Controllers\Covid19\VaccineController::cleanupAppointment();
        // })->dailyAt('4:00')
        //     ->name('cleanupAppointment')
        //     ->onOneServer();
        
        $schedule->call(function() {
            $email = env('VACCINE_STATISTICS_EMAIL','');
            if (!empty($email)) {
                $email = explode(',',$email);
                \Mail::to($email)->send(new \App\Mail\VaccineStatistics(\Carbon\Carbon::now()));
            } else {
                return null;
            }
        })->dailyAt('18:00')
            ->name('VaccineStatisticsEmailDaily')
            ->onOneServer();

        $schedule->call(function() {
            $email = env('VACCINE_STATISTICS_EMAIL','');
            if (!empty($email)) {
                $email = explode(',',$email);
                \Mail::to($email)->send(new \App\Mail\VaccineStatistics(\Carbon\Carbon::now()->subDays(6),\Carbon\Carbon::now()));
            } else {
                return null;
            }
        })->weeklyOn(7, '18:00')
            ->name('VaccineStatisticsEmailWeekly')
            ->onOneServer();

        $schedule->call(function() {
            $email = env('VACCINE_STATISTICS_EMAIL','');
            if (!empty($email)) {
                $email = explode(',',$email);
                \Mail::to($email)->send(new \App\Mail\VaccineAppointments(\Carbon\Carbon::now(),\Carbon\Carbon::now()->addDays(6)));
            } else {
                return null;
            }
        })->dailyAt('07:00')
            ->name('VaccineAppointmentsEmailDaily')
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
