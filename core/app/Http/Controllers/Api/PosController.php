<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreLoyaltyPointRequest;
use App\Http\Requests\API\ValidateUserPinCodeRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PosController extends Controller
{
    public function validatePinCode(ValidateUserPinCodeRequest $validateUserPinCodeRequest): JsonResponse
    {
        $user = User::findUsingPinCode($validateUserPinCodeRequest->input('pin_code'));
        return response()->json([
            'balance' => $user->loyaltyPointsBalance(),
            'username' => "{$user->firstname} {$user->lastname}"
        ]);
    }

    public function storeLoyaltyPoint(StoreLoyaltyPointRequest $request)
    {
        $user = User::findUsingPinCode($request->input('pin_code'));
        return $user->loyaltyPoints()->create([
            'type' => $request->input('type'),
            'description' => "",
            'pos_order_id' => $request->input('order_id'),
            'amount' => $request->input('amount'),
            'pin_code' => $request->input('pin_code')
        ]);
    }

}
