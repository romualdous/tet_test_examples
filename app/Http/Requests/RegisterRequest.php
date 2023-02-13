<?php

namespace App\Http\Requests;

use App\Rules\ValidPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'phone_number' => ['required', 'string', new ValidPhoneNumber],
            'type'         => 'required|string|in:customer,listener,both',
            'debug_key' => 'string',
            'platform' => 'required|string|in:android,ios'
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'       => 'User type can be either customer or listener',
            'type.required' => 'User type must be provided upon registration ("customer", "listener")'
        ];
    }
}
