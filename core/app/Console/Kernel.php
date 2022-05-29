<?php

namespace App\Console;

use App\Console\Commands\FirstInvestmentWithdrawCommand;
use App\Console\Commands\InvestmentsRevenueCalculatorCommand;
use App\Console\Commands\MonthlyInvestmentRevenueWithdrawCommand;
use App\Console\Commands\MineCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\MineCommand::class
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command(InvestmentsRevenueCalculatorCommand::class)->everyTwoHours();
        // $schedule->command(MonthlyInvestmentRevenueWithdrawCommand::class)->everyTwoHours();
        // $schedule->command(FirstInvestmentWithdrawCommand::class)->everyTwoHours();

        $schedule->command("demo:cron")->everyMinute();
        // $schedule->call(function() {
        //     // Log::where('user_id', 678)->first()->delete();
        //     // $a = new Log();
        //     // $a->user_id = 90;
        //     // $a->save();
        //     // $a['user_id'] = 90;
        //     // Log::create($a);
        //     Log::insert([
        //         'user_id' => 89
        //         ]);
        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
