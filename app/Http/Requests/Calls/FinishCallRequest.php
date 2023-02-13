<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class FinishCallRequest extends FormRequest
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
    public function rules()
    {
        return [
            'conversation_id' => 'required|integer|exists:conversations,id'
        ];
    }
}
