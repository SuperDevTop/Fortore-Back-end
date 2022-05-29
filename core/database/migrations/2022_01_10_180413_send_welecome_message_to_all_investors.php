<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SendWelecomeMessageToAllInvestors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        foreach (User::get() as $user) {
//            $password = Str::random(10);
//            $user->forceFill([
//                'password' => Hash::make($password)
//            ]);
//            $message = "مرحبا {$user->fullname()},
//بك في التطبيق الخاص بالمستثمرين , وصلتك هذه الرسالة لانك مستثمر معنا، للدخول لحسابك يرجى زيارة الرابط التالي https://fortoremallloyalty.com/login
// اسم المستخدم : $user->username
// كلمة المرور: $password ";
//            send_sms_message($user->mobile,$message);
//        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
