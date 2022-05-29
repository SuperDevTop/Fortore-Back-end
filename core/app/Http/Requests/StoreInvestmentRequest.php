<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * @property mixed amount
 */
class StoreInvestmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            "fixed_amount" => ["nullable", "numeric"],
            'plan_id' => ['required', 'exists:plans,id'],
            'amount' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                $value = (float)$value;
                $plan = $this->getPlan();
                if ($plan->fixed_amount == 0) {
                    if ($this->getAmount() < $plan->minimum)
                        $fail('Minimum Invest ' . getAmount($plan->minimum) . ' ');
                    if ($this->getAmount() > $plan->maximum)
                        $fail('Maximum Invest ' . getAmount($plan->minimum) . ' ');
                } else {
                    if ($value != $plan->fixed_amount) {
                        $fail('The ' . $attribute . ' should match fixed amount.');
                    }
                }

                if (!$this->isWalletType('checkout') && $value > Auth::user()->{$this->getWalletType()}) {
                    $fail('The ' . $attribute . ' is greater than wallet amount.');
                }
            },],

            'wallet_type' => 'required|in:deposit_wallet,interest_wallet,checkout',
        ];
    }

    public function getPlan(): Plan
    {
        return Plan::where('id', $this->input('plan_id'))->where('status', 1)->firstOrFail();
    }

    public function getAmount(): float
    {
        return (float)$this->input('amount');
    }

    public function isWalletType($walletType): bool
    {
        return $this->input('wallet_type') === $walletType;
    }

    public function getWalletType()
    {
        return $this->input('wallet_type');
    }
}
