<?php

namespace App\Http\Controllers\Gateway\my_fatoorah;

use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\GeneralSetting;
use Basel\MyFatoorah\MyFatoorah;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Session;

class ProcessController extends Controller
{


    /*
     * my fatoorah Gateway
     */

    public static function process(Deposit $deposit)
    {

        $instance = MyFatoorah::getInstance(!config('myfatoorah.is_live'));
        $payment = $instance->sendPayment(
            Auth::user()->username,
            $deposit->amount,
            [

                "CustomerReference" => $deposit->trx,
                'DisplayCurrencyIso' => 'SAR',
                "CallBackUrl" => route('user.deposit'),
                "ErrorUrl" => route('user.deposit'),
            ]
        );
        return json_encode(['redirect' => true, "redirect_url" => $payment["Data"]["InvoiceURL"]]);
    }


    public function ipn(Request $request)
    {


    }
}
