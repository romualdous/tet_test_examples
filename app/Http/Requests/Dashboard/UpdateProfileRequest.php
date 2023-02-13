<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'full_name'     => 'sometimes|max:200',
            'photo'         => 'sometimes|image|max:' . 1024 * 5,
            'bio'           => 'max:500',
            'email'         => 'sometimes|email',
            'date_of_birth' => 'date|date_format:d.m.Y',
            'gender'        => 'sometimes|in:male,female',
            'type'          => 'sometimes|in:customer,listener',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'Photo provided cannot exceed size of 5 MB'
        ];
    }
}
