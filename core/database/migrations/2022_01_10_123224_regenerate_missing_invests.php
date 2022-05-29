<?php

use App\Imports\InvestmentsImport;
use Illuminate\Database\Migrations\Migration;
use Maatwebsite\Excel\Facades\Excel;

class RegenerateMissingInvests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Excel::import(new InvestmentsImport, storage_path('app/investments.xlsx'));
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
