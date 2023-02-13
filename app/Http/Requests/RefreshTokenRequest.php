<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $conversation = $this->getConversation();

        return $conversation?->isOngoing()
            && $conversation->hasParticipant(auth()->guard('sanctum')->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'channel' => 'exists:conversations,channel'
        ];
    }

    /**
     * @return Conversation|null
     */
    public function getConversation(): ?Conversation
    {
        return Conversation::whereChannel($this->get('channel'))->firstOrFail();
    }
}
