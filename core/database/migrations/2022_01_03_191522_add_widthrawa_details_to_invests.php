<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWidthrawaDetailsToInvests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invests', function (Blueprint $table) {
            $table->timestamp('last_withdraw_at')->nullable();
            $table->integer('withdraw_count')->default(0);
            $table->float('withdraw_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invests', function (Blueprint $table) {
            $table->dropColumn('last_withdraw_at');
            $table->dropColumn('withdraw_count');
            $table->dropColumn('withdraw_amount');
        });
    }
}
