<?php

namespace App\Http\Requests;

use App\Rules\ValidPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
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
            'phone_number'      => ['required', 'string', 'exists:users,phone_number', new ValidPhoneNumber],
            'verification_code' => 'required|int'
        ];
    }
}
