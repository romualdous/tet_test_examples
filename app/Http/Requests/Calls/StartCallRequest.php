<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class StartCallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'listener_id' => 'required|integer|exists:users,id',
            'topic_id' => 'nullable|integer'
        ];
    }
}
