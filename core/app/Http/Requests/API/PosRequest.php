<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class PosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->header('Access-Key') === config('services.pos_app.access_key');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'pin_code' => ['required', 'exists:users,pin_code']
        ];
    }
}
