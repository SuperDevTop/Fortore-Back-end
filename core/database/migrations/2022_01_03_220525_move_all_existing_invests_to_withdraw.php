<?php

use App\Models\Invest;
use App\Repositories\InvestmentRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveAllExistingInvestsToWithdraw extends Migration
{
    /**
     * @var InvestmentRepository
     */
    private $repo;

    public function __construct()
    {
        $this->repo = new InvestmentRepository();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Invest::query()->where('status', 1)->where('withdraw_count', 0)->get() as $invest) {
            $this->repo->makeWithdraw($invest);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('withdraw', function (Blueprint $table) {
            //
        });
    }
}
