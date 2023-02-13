<?php

namespace App\Http\Controllers;

use App\Events\RatingCreated;
use App\Exceptions\ConversationException;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    /**
     * @param StoreRatingRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws ConversationException
     */
    public function store(StoreRatingRequest $request, User $user): JsonResponse
    {
        $conversation = Conversation::findOrFail($request->get('conversation_id'));
        $currentUser = auth()->guard('sanctum')->user();

        if (! $conversation->isFinished()) {
            throw ConversationException::callNotFinished();
        }

        if (! $conversation->hasParticipant($user) || ! $conversation->hasParticipant($currentUser)) {
            throw ConversationException::userNotParticipant();
        }

        if ($user->haveBeenRatedIn($conversation)) {
            throw ConversationException::userHaveAlreadyBeenRated($user);
        }

        $conversation->ratings()->create([
            'reviewer_id'      => $currentUser->id,
            'recipient_id'     => $user->id,
            'feels_better'     => $currentUser->id === $conversation->caller_id ? (bool) $request->get('feels_better') : null,
            'would_talk_again' => (bool) $request->get('would_talk_again'),
            'rating'           => (int) $request->get('rating'),
            'comment'          => $request->get('comment')
        ]);

        event(new RatingCreated(recipient: $user));

        return response()->json([
            'success' => true,
            'message' => 'Rating created successfully',
            'data'    => []
        ]);
    }
}
