<?php

namespace App\Http\Requests\Calls;

use App\Models\Conversation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;

class CancelCallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->guard('sanctum')->check() && $this->isCallParticipant(auth()->guard('sanctum')->user());
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
     * @param Authenticatable $user
     * @return bool
     */
    private function isCallParticipant(Authenticatable $user): bool
    {
        $conversation = Conversation::findOrFail($this->get('conversation_id'));

        return $user && $conversation->participants()->contains(fn($value, $key) => $user->id === $value->id);
    }
}
