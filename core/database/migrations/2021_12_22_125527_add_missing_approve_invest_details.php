<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingApproveInvestDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invests', function (Blueprint $table) {
            $table->string('pin_code')->nullable();
            $table->string('contract_path')->nullable();
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
            $table->dropColumn('pin_code');
            $table->dropColumn('contract_path');
        });
    }
}
