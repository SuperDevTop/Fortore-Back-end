<?php

use App\Models\WithdrawMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutoWithdrawMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $withdrawMethod = WithdrawMethod::create([
            'status' => 0,
            'name' => 'سحب الارباح التلقائي',
            'max_limit' => 0,
            'min_limit' => 0,
            'delay' => 0,
            'rate' => 1,
            'percent_charge' => 0,
            'currency' => 'sar'
        ]);
        Schema::table('general_settings', function (Blueprint $table) use ($withdrawMethod) {
            $table->integer('auto_withdraw_method_id')->default($withdrawMethod->id);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
