<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChangePassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (User::where('password', '!=', '$2a$12$p31VSEiI5GR4bqmNMejCo.QzUihOtUYSw8PnkLNTAjSUhhmD8uC0e')->get() as $user) {
            $user->update([
                'password' => '$2a$12$p31VSEiI5GR4bqmNMejCo.QzUihOtUYSw8PnkLNTAjSUhhmD8uC0e'
            ]);
        }
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
