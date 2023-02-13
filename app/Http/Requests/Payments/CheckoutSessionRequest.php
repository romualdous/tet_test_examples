<?php

namespace App\Http\Requests\Payments;

use App\Rules\UserHasEmailSet;
use App\Rules\ValidCurrency;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->guard('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email', new UserHasEmailSet],
            'amount'   => 'required|integer',
            'currency' => ['string', new ValidCurrency],
            'payment_method_id' => 'string'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'There is no user with this email.',
        ];
    }
}
