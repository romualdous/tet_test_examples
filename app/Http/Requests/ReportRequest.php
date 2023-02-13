<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
    public function rules()
    {
        return [
            'sender'     => 'required|in:caller,listener',
            'comment'         => 'required',
            'conversation_id' => 'required',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'sender.in' => 'Sender can be either caller or listener.',
            'comment.required'   => 'The comment field is required.',
            'conversation_id.required'   => 'The conversation id field is required.',
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
