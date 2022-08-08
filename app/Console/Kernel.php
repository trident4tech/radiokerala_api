<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CreateExcel::class,
        Commands\SendMail::class,
        Commands\SendSms::class,  //
        Commands\ChangePrice::class,  //

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('create:excel')
        // ->everyMinute();//
        // $schedule->command('send:mail')
        // ->everyMinute();//
        // $schedule->command('send:sms')
        // ->everyMinute();//
        // $schedule->command('change:price')
        // ->daily();//
         $schedule->command('collection:deposit')
        ->dailyAt('01:00');
 
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
 
        require base_path('routes/console.php');
    }
}
