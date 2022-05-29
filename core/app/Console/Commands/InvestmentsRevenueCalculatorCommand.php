<?php

namespace App\Console\Commands;

use App\Models\GeneralSetting;
use App\Models\Invest;
use App\Repositories\InvestmentRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class InvestmentsRevenueCalculatorCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'InvestmentsRevenueCalculatorCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var InvestmentRepository
     */
    private $investmentRepo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->investmentRepo = new InvestmentRepository();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $gnl = GeneralSetting::getActiveSetting();
        $gnl->updateCronJobDate();
        if ($gnl->isHoliday(Carbon::now()))
            return 0;
        Invest::eligibleToRevenue($now)->get()->each(function ($invest) use ($gnl) {
            $this->investmentRepo->makeInvestmentRevenue($invest, $gnl);
        });
        return 0;
    }
}
