<?php

namespace App\Http\Requests\API;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreLoyaltyPointRequest extends PosRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'order_id' => 'required',
            'amount' => ['required', Rule::when($this->input('type') === 'sub', [function ($attribute, $value, $fail) {
                $user = User::findUsingPinCode($this->input('pin_code'));
                $balance = $user->loyaltyPointsBalance();
                if ($balance < (float)$value) {
                    $fail('The ' . $attribute . ' is invalid.');
                }
            },])],
            'type' => 'required|string|in:add,sub'
        ]);
    }
}
