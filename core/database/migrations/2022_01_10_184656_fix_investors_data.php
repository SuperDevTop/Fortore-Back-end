<?php

use App\Imports\FixIvestorDataImport;
use App\Imports\InvestmentsImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class FixInvestorsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users',function (Blueprint $blueprint){
            $blueprint->string('birth_day')->change()->nullable();
        });
        Excel::import(new FixIvestorDataImport(), storage_path('app/updated_data.xlsx'));

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
