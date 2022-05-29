<?php

namespace App\Console\Commands;

use App\Models\Invest;
use App\Repositories\InvestmentRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MonthlyInvestmentRevenueWithdrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MonthlyInvestmentRevenueWithdrawCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var InvestmentRepository
     */
    private $repo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->repo = new InvestmentRepository();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        Invest::where('status', 1)
            ->where('last_withdraw_at', '<', Carbon::now()
                ->subDays(30))
            ->where('withdraw_count', '>', 0)
            ->get()
            ->each(function (Invest $invest) {
                $this->repo->makeWithdraw($invest);
            });
        return 0;
    }
}
