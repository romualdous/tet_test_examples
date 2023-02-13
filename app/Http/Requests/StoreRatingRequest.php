<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
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
            'rating'           => 'required|integer|min:1|max:5',
            'comment'          => 'sometimes|string|min:3',
            'conversation_id'  => 'required|integer|exists:conversations,id',
            'feels_better'     => 'required|boolean',
            'would_talk_again' => 'sometimes|boolean'
        ];
    }
}
