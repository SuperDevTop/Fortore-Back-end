<?php

namespace App\Http\Controllers\Gateway\my_fatoorah;

use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use Illuminate\Http\Request;

class WebhookController
{
    public function statusChanged(Request $request)
    {
        $request->validate([
            'Event' => 'required|in:TransactionsStatusChanged',
            "Data" => ["required"],
            "Data.TransactionStatus" => "required|string|in:SUCCESS"
        ]);
        $data = collect($request->input('Data'));
        $depositTx = $data->get('CustomerReference');
        $deposit = Deposit::where('trx', $depositTx)->where("status",0)->first();
        if ($deposit) {
            PaymentController::userDataUpdate($deposit->trx);
        }
    }
}
