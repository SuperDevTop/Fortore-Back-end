<?php

namespace App\Http\Requests;

use App\Models\Invest;
use Illuminate\Foundation\Http\FormRequest;

class ValidateInvestApproveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'pin_code' => 'required|string'
        ];
    }

    public function validatePinCode(Invest $invest)
    {
        $this->validate([
            'pin_code' => 'in:' . $invest->pin_code
        ]);
    }
}
