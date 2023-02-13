<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class AcceptCallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = auth()->guard('sanctum')->user();

        return $user && $user->isListener();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'conversation_id' => 'required|integer|exists:conversations,id'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'conversation_id.exists' => 'Conversation has not been initiated and does not exist in database'
        ];
    }
}
