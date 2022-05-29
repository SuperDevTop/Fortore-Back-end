<?php

namespace App\Jobs;

use App\Models\Invest;
use App\Repositories\InvestmentRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mpdf\MpdfException;

class MakeInvestmentPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Invest
     */
    private $invest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invest $invest)
    {
        //
        $this->invest = $invest;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws MpdfException
     */
    public function handle()
    {
        $investRepo = new InvestmentRepository();
        $this->invest->update([
            'contract_path' => $investRepo->toPdf($this->invest),
            'contract_path2' => $investRepo->toPdf2($this->invest)
        ]);
    }
}
