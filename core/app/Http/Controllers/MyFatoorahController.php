<?php

namespace App\Http\Controllers;

use App\Models\PaymentInvoice;
use Basel\MyFatoorah\MyFatoorah;
use Exception;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Deposit;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Gateway\PaymentController;

class MyFatoorahController extends Controller
{
    public $myfatoorah;

    public function __construct()
    {
        $this->myfatoorah = MyFatoorah::getInstance(true);
    }


    public function index()
    {
        $user = auth()->user();
        $amount = request()->amount;
        try {
            $result = $this->myfatoorah->sendPayment(
                Auth::user()->getFullnameAttribute(),
                $amount,
                [
                    //     'MobileCountryCode',
                    'CustomerMobile' => $user->mobile,
                    //     'CustomerEmail',
                    //     'Language' =>"AR",
                    'CustomerReference' => "1323",  //orderID
                    // 'CustomerCivilId' => "321",
                    'UserDefinedField' => $user->id, //clientID
                    //     'ExpireDate',
                    //     'CustomerAddress',
                    "InvoiceItems" => [
                        [
                            "ItemName" => "deposit",
                            "Quantity" => 1,
                            "UnitPrice" => $amount,
                        ]
                    ]
                ]
            );
            if ($result && $result['IsSuccess'] == true) {
                return redirect($result['Data']['InvoiceURL']);
            }

        } catch (Exception $e) {
            echo $e->getMessage();
            echo $e->getResponse()->getBody()->getContents();

            //    dd($e  ,$e->getResponse()->getBody()->getContents() );
        }
    }

    public function successCallback(Request $request)
    {

        //  "paymentId" => "060641960331928262"
        //   "Id" => "060641960331928262"

        if (array_key_exists('paymentId', $request->all())) {
            $result = $this->myfatoorah->getPaymentStatus('paymentId', $request->paymentId);

            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Paid") {

                // Logic after success
                $this->createInvoice($result['Data']);
                echo "success payment";
            }
        }
    }

    public function failCallback(Request $request)
    {
        if (array_key_exists('paymentId', $request->all())) {
            $result = $this->myfatoorah->getPaymentStatus('paymentId', $request->paymentId);

            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Pending") {

                // Logic after fail
                $error = end($result['Data']['InvoiceTransactions'])['Error'];
                echo "Error => " . $error;
            }
        }
    }

    public function createInvoice($request)
    {
        $paymentarray = array_merge($request, end($request['InvoiceTransactions']));
        $paymentarray['order_id'] = $paymentarray['CustomerReference'];
        $paymentarray['client_id'] = $paymentarray['UserDefinedField'];

        $PaymentInvoice = PaymentInvoice::create($paymentarray);
    }

    // create a deposit for user if the payment success
    public function createDeposit($request)
    {
    }
}
