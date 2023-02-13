<?php

namespace App\Http\Controllers;

use App\Events\Calls\CallCancelled;
use App\Events\Calls\CallFinished;
use App\Events\Calls\CallOngoing;
use App\Exceptions\ConversationException;
use App\Exceptions\PushNotificationException;
use App\Http\Requests\Calls\AcceptCallRequest;
use App\Http\Requests\Calls\CancelCallRequest;
use App\Http\Requests\Calls\FinishCallRequest;
use App\Http\Requests\Calls\StartCallRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\Conversation;
use App\Models\Notification_logdata;
use App\Models\Topic;
use App\Models\User;
use App\Services\Agora\Client;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    const NOTIF_CALL_INCOMING = 'notification_call_incoming';
    const NOTIF_CALL_CANCELLED = 'notification_call_cancelled';
    const NOTIF_CALL_FINISHED = 'notification_call_finished';
    const NOTIF_CALL_ACCEPTED = 'notification_call_accepted';

    /**
     * @param StartCallRequest $request
     * @return JsonResponse
     * @throws ConversationException
     * @throws PushNotificationException
     */

     /**
     * @OA\Post  (
     *      path="/api/call",
     *      description="Start new call",
     *      tags={"In-app phone calls"},
     *      summary="Start new call between customer and listener",
     *      description="Start new call between customer and listener",
     *    @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"listener_id"},
     *              @OA\Property(property="listener_id", type="integer", example="48"),
     *              @OA\Property(property="topic_id", type="integer", example="2"),
     *          )
     * ),
     *    @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *                  @OA\Property(property="success", type="string", example="true"),
     *                  @OA\Property(property="messageaev", type="string", example="Call has been initiated"),
     *             type="object",
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                example={
     *                        "conversation_id": 49,
     *                       "channel": "7oTM0gE3u1exWDOJkV4FL5E7QWLS1a",
     *                        "token": "006bef91d694a284fb6ae89d8ceb90ceadeIAD74EY8lpjRZzRsuCLzJDroi7xR3wKUxFD8DZNtJ1nZdsWr3f0h39v0IgBJhhED+AjtYgQAAQDQuetiAwDQuetiAgDQuetiBADQueti",
     *                        "caller_balance": 29,
     *                        "listener_balance": 2997.92,
     *                       "topic_weight": 0
     *                },
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="conversation_id",
     *                         type="integer",
     *                         example="49"
     *                      ),
     *                      @OA\Property(
     *                         property="channel",
     *                         type="string",
     *                         example="7oTM0gE3u1exWDOJkV4FL5E7QWLS1a"
     *                      ),
     *                      @OA\Property(
     *                         property="token",
     *                         type="string",
     *                         example="4"
     *                      ),
     *                           @OA\Property(
     *                         property="caller_balance",
     *                         type="integer",
     *                         example="4"
     *                      ),
     *                          @OA\Property(
     *                         property="listener_balance",
     *                         type="double",
     *                         example="2997.92"
     *                      ),
     *                            @OA\Property(
     *                         property="topic_weight",
     *                         type="integer",
     *                         example="4"
     *                      ),
     *              )
     *          )
     *      )
     *    ),
     *    )
     */

    public function start(StartCallRequest $request): JsonResponse
    {
        $listener = User::find($request->get('listener_id'));
        $conversation = Conversation::start(auth()->guard('sanctum')->user(), $listener,$request->topic_id);
        $getWeight = Topic::where('id', $request->topic_id)->first();
        /** @var User $caller */
        $caller = $request->user();

        $get_title = $this->getTranslation($listener->language,["notifications",'incoming_call']);
        $defaultPressNotification = $this->getTranslation($listener->language,["notifications",'press_to_open']);

        $this->sendPushNotification(
            $listener->device_token_listener,
            array_merge(
                $conversation->only(['id', 'channel', 'token']),
                [
                    'type'   => self::NOTIF_CALL_INCOMING,
                    'caller' => $caller->only(['full_name', 'gender', 'age', 'id']),
                    'caller_balance' => $caller->balance,
                    'listener_balance' => $listener->balance,
                    'topic_weight' => $getWeight->weight ?? 0
                ]
            ),
            title: "{$get_title} {$caller->full_name}",
            body: "{$defaultPressNotification}",
            user_id: $listener->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Call has been initiated',
            'data'    => [
                'conversation_id' => $conversation->id,
                'channel'         => $conversation->channel(),
                'token'           => $conversation->token(),
                'caller_balance' => $caller->balance,
                'listener_balance' => $listener->balance,
                'topic_weight' => $getWeight->weight ?? 0
            ]
        ]);
    }

    /**
     * @param AcceptCallRequest $request
     * @return JsonResponse
     * @throws ConversationException|PushNotificationException
     */
    public function accept(AcceptCallRequest $request): JsonResponse
    {
        $conversation = Conversation::find($request->get('conversation_id'));
        if (!$conversation->isAcceptable()) {
            throw ConversationException::callCannotBeAccepted();
        }
        $conversation->markAsOngoing();

        event(new CallOngoing($conversation->fresh()));

        $request->user()->update([
            'status' => User::STATUS_ON_CALL
        ]);
        $caller = User::where('id',$conversation->caller_id)->first();

        if(!is_null($caller->device_token_customer)) {
            $this->sendPushNotification(
                $caller->device_token_customer,
                ['call_accepted' => $conversation->id,'type' => self::NOTIF_CALL_ACCEPTED,],
                title: null,
                body: null,
                user_id: $caller->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Call has been accepted',
            'data'    => []
        ]);
    }

    /**
     * @param CancelCallRequest $request
     * @return JsonResponse
     * @throws ConversationException|PushNotificationException
     */
    public function cancel(CancelCallRequest $request): JsonResponse
    {
        $conversation = Conversation::find($request->get('conversation_id'));

        if (!$conversation->isCancellable()) {
            throw ConversationException::callCannotBeCancelled();
        }

        $conversation->markAsCancelled();

        event(new CallCancelled($conversation->fresh()));

        $listener = User::find($conversation->listener_id);
        $defaultPressNotification = $this->getTranslation($listener->language,["notifications",'press_to_open']);
        $get_title = $this->getTranslation($listener->language,["notifications",'call_interrupted']);
        $this->sendPushNotification(
            $listener->device_token_listener,
            $conversation->only(['id', 'channel', 'token']) + ['type' => self::NOTIF_CALL_CANCELLED],
            title: "{$get_title}",
            body: "{$defaultPressNotification}",
            user_id: $listener->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Call has been cancelled',
            'data'    => []
        ]);
    }

    /**
     * @param FinishCallRequest $request
     * @return JsonResponse
     * @throws ConversationException|PushNotificationException
     */
    public function finish(FinishCallRequest $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($request->get('conversation_id'));

        if (!$conversation->isOngoing()) {
            throw ConversationException::callNotActive();
        }

        $conversation->markAsFinished();

        event(new CallFinished($conversation->fresh()));

        $listener = User::find($conversation->listener_id);
        // FIX , now checking listeners status,to prevent bug. DIA-1019
        if ($listener->status == 'offline') {
            $getNewStatus = User::STATUS_OFFLINE;
        }
        else {
            $getNewStatus = User::STATUS_ONLINE;
        }
        $listener->update([
            'status' => $getNewStatus,
            'balance' => $listener->balance + $conversation->duration
        ]);

        $customer = User::find($conversation->caller_id);
        $customer->update([
            'balance' => $customer->balance - $conversation->duration
        ]);
        $defaultPressNotification = $this->getTranslation($customer->language,["notifications",'press_to_open']);
        $get_title = $this->getTranslation($customer->language,["notifications",'call_ended']);
        $this->sendPushNotification(
            $customer->device_token_listener,
            $conversation->only(['id', 'channel', 'token']) + ['type' => self::NOTIF_CALL_FINISHED],
            title: "{$get_title}",
            body: "{$defaultPressNotification}",
            user_id: $customer->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Call successfully finished',
            'data'    => []
        ]);
    }

    /**
     * @param RefreshTokenRequest $request
     * @return JsonResponse
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Channel token regenerated',
            'data'    => [
                'token' => app(Client::class)->createToken(channelName: $request->get('channel'))
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        $listener_id = $request->listener;
        $caller_id = $request->caller;

        if (!$listener_id && !$caller_id) {

            $conversations = Conversation::with('listener', 'caller')
                ->where('listener_id', '=', $user->id)
                ->where(function ($query) {
                    $query->where('status', '=', 'finished')
                        ->orWhere('status', '=', 'cancelled');
                })
                ->orWhere('caller_id', '=', $user->id)
                ->where(function ($query) {
                    $query->where('status', '=', 'finished')
                        ->orWhere('status', '=', 'cancelled');
                });
        }
        if ($listener_id && $caller_id) {

            return response()->json([
                'success' => false,
                'message' => 'Both parameters are not allowed.' // Because user could not be in them, and next conditions work as if user is one of parameters.
            ]);
        }
        if (!$listener_id && $caller_id) {

            if ($caller_id == $user->id) {

                $conversations = Conversation::with('listener', 'caller')
                    ->Where('caller_id', $user->id)
                    ->where(function ($query) {
                        $query->where('status', '=', 'finished')
                            ->orWhere('status', '=', 'cancelled');
                    });
            } else {

                $conversations = Conversation::with('listener', 'caller')
                    ->where('listener_id', $user->id)
                    ->Where('caller_id', $caller_id)
                    ->where(function ($query) {
                        $query->where('status', '=', 'finished')
                            ->orWhere('status', '=', 'cancelled');
                    });
            }
        }
        if ($listener_id && !$caller_id) {

            if ($listener_id == $user->id) {

                $conversations = Conversation::with('listener', 'caller')
                    ->Where('listener_id', $user->id)
                    ->where(function ($query) {
                        $query->where('status', '=', 'finished')
                            ->orWhere('status', '=', 'cancelled');
                    });
            } else {

                $conversations = Conversation::with('listener', 'caller')
                    ->where('listener_id', $listener_id)
                    ->Where('caller_id', $user->id)
                    ->where(function ($query) {
                        $query->where('status', '=', 'finished')
                            ->orWhere('status', '=', 'cancelled');
                    });
            }
        }

        $data = $conversations->orderBy('created_at')->get();
        foreach ($data as $conversation) {
            $getWeight = Topic::where('id', $conversation['topic_id'])->first();
            $conversation->weight = $getWeight != null ? $getWeight->weight : 0;
        }
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Post  (
     *      path="/api/call/finishall",
     *      description="Finish all conversation of user,can be included exception id's ",
     *      tags={"In-app phone calls"},
     *      summary="Finish all conversation of user,can be included exception id's",
     *      description="Finish all conversation of user,can be included exception id's",
     *    @OA\RequestBody(
     *    required=false,
     *    description="U can provide array of id's what shoud be ignored,to finish them.",
     *    @OA\JsonContent(
     *       @OA\Property(property="excludeCalls", type="array", example=
     *     {
     *      1,2,3,4
     *     },
     *           @OA\Items(
     *          )
     *         )
     *     )
     * ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="Operation_done"),
     *          )
     *      )
     *    )
     */

    public function listFinishAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'excludeCalls' => 'array'
        ]);

        if(!is_null($request->excludeCalls)) {
            $conversations = Conversation::
            where('caller_id', $user->id)
                ->whereNotIn('status', ['finished','cancelled'])
                ->whereNotIn('id', $request->excludeCalls)
            ->orWhere('listener_id',$user->id)
                ->whereNotIn('status', ['finished','cancelled'])
                ->whereNotIn('id', $request->excludeCalls)
            ->get();
        }
        else {
            $conversations = Conversation::
                where('caller_id', $user->id)
                    ->whereNotIn('status', ['finished','cancelled'])
                ->orWhere('listener_id',$user->id)
                    ->whereNotIn('status', ['finished','cancelled'])
            ->get();
        }

        foreach ($conversations as $row) {
            if ($row->status == 'requested' || $row->check_time == null) {
                    $row->markAsCancelled();
                    continue;
            }
                $time = $row->check_time;

            if (!is_null($time) && !is_null($row->started_at)) {
                $row->checkTimeToFinish($time);
                event(new CallFinished($row->fresh()));
                $listener = User::find($row->listener_id);
                $listener->update([
                    'status' => User::STATUS_ONLINE,
                    'balance' => $listener->balance + $row->duration
                ]);
                $customer = User::find($row->caller_id);
                $customer->update([
                    'balance' => $customer->balance - $row->duration
                ]);
            }
            else {
                $row->markAsCancelled();
            }

        }

        return response()->json([
           'data' => 'Operation_done.'
        ]);

    }

    /**
     * @OA\Post  (
     *      path="/api/call/check",
     *      description="Set time check_time of provided conversation id.",
     *      tags={"In-app phone calls"},
     *      summary="Set time check_time of provided conversation id.",
     *      description="Set time check_time of provided conversation id.",
     *    @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"conversation_id"},
     *              @OA\Property(property="conversations_id", type="integer", example="1"),
     *          )
     * ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="check_time is updated."),
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="No conversation found",
     *           @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="Conversation not found"),
     *          )
     *      )
     *    )
     */

    public function checkConversationTime(Request $request) {

         $request->validate([
           'conversation_id' => 'required|numeric'
        ]);

        $findConversation = Conversation::where('id', $request->conversation_id)->first();
        if (is_null($findConversation)) {
            return response()->json([
                'data' => 'Conversation not found'
            ], 404);
        }
        $findConversation->check_time = now();
        $findConversation->save();


        return response()->json([
            'data' => 'check_time is updated.'
        ]);

    }

    /**
     * Send push notification to Firebase.
     *
     * @param string|null $deviceToken
     * @param array $data
     * @param string $title
     * @param string $body
     * @param string $user_id
     * @return mixed
     * @throws PushNotificationException
     */
    private function sendPushNotification(?string $deviceToken, array $data, ?string $title , ?string $body, string $user_id): bool
    {
        if (!$deviceToken) {
            $this->storeNotificationData($user_id,$deviceToken,$title,$body,$data,PushNotificationException::deviceTokenNotSet());
            throw PushNotificationException::deviceTokenNotSet();
        }

        $url = config('firebase.url');
        $apiKey = $this->determineApiKey($data);

        $fields = [
            'to'           => $deviceToken,
            'notification' => [
                'title' => $title,
                'body'  => $body
            ],
            'data'         => $data,
            'priority'     => 'high',
            'content_available' => true,

        ];

        $headers = [
            'Content-Type:application/json',
            'Authorization: key=' . $apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        $this->storeNotificationData($user_id,$deviceToken,$title,$body,$data,$result ?? curl_error($ch));

        if (!$result) {
            throw new Exception("Failed to send push notification! Message:" . curl_error($ch));
        }

        curl_close($ch);

        return true;
    }

    /**
     * Determine which FCM instance API key to use to relay message.
     *
     * @param array $data
     * @return string
     */
    public function determineApiKey(array $data): string
    {

            $instance = in_array($data['type'], [self::NOTIF_CALL_INCOMING, self::NOTIF_CALL_CANCELLED])
                ? 'listener'
                : 'customer';
            return config("firebase.api_key.{$instance}");

    }

    public function storeNotificationData($send_to_user,$deviceToken,$title,$body,$conversation_datas,$response = null) {

        Notification_logdata::create([
            'sendToUser' => $send_to_user,
            'device_token'         => $deviceToken,
            'title'       => $title,
            'body'       => $body,
            'data' => json_encode($conversation_datas),
            'curl_response' => $response
        ]);
    }

}
