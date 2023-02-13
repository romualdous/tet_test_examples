<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'full_name'    => 'sometimes|max:200',
            'email'        => 'required|unique:users,email',
            'age'          => 'integer|between:1,150',
            'gender'       => 'in:male,female',
            'phone_number' => 'string|min:8',
            'type'         => 'required|in:customer,listener',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'gender.in' => 'User can be either male or female',
            'type.in'   => 'User can only be either customer or listener',
        ];
    }
}
