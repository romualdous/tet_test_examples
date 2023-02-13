<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
            return [
                'full_name'     => 'string|min:3',
                'email' => [
                    'nullable',
                    Rule::unique('users')->ignore(Auth::id()),
                    'email'
                ],
                'gender'        => 'in:male,female',
                'status'        => 'in:online,offline,on-call',
                'bio'           => 'min:3',
                'profile_url'   => [
                    Rule::unique('users')->ignore(Auth::id()),
                ],
                'profile_image' => 'image',
                'spoken_languages.*' => 'in:lv,ru,en',
                'consent_to_agreement' => 'boolean',
                'oldenough' => 'boolean'
            ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'gender.in' => 'User can be either male or female.',
            'profile_image.image'   => 'File is not a valid image.',
            'profile_url.unique'   => 'Profile URL already taken.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {

        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->getMessageBag(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
