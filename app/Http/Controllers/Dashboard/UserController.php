<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\Users\UserTypeChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUserRequest;
use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserController extends Controller
{
    /**
     * @var int
     */
    private int $perPage = 15;

    /**
     * @OA\Get (
     *      path="/api/admin-panel/users",
     *      description="Returns datas of user - > with filters and search option and sort options",
     *      tags={"Admin: users"},
     *      summary="Returns datas of user - > with filters and search option and sort options",
     *      description="Returns datas of user - > with filters and search option and sort options",
     *          @OA\Parameter(
     *          name="type",
     *          description="Your can provide filter option to all incomming datas in: customer,listener",
     *          required=false,
     *          example="customer",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="searchName",
     *          description="U can provide search name ,that will be filtered and bring back datas like this name.",
     *          required=false,
     *          example="Mike",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="searchPhone",
     *          description="U can provide search phone ,that will be filtered and bring back datas like this phone.",
     *          required=false,
     *          example="25252525",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="searchEmail",
     *          description="U can provide search email ,that will be filtered and bring back datas like this email.",
     *          required=false,
     *          example="test@test.lv",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="order",
     *          description="U can provide order/sort type,with all datas will be sorted and returned back. in:type | full_name | phone_number | email | rating | created_at | balance | date_of_birth | succesfull_calls | total_time",
     *          required=false,
     *          example="rating",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="order_type",
     *          description="Your can provide order type -> in: desc,asc ( WORKS WHEN ORDER IS SPECIFIED)",
     *          required=false,
     *          example="desc",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *    @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                example={{
     *                          "id": 63,
     *                           "full_name": null,
     *                           "photo": null,
     *                           "email": null,
     *                           "date_of_birth": "06.09.2021",
     *                           "phone_number": "+3712608379423",
     *                           "gender": null,
     *                           "type": "listener",
     *                           "rating": null,
     *                           "status": "offline",
     *                           "bio": null,
     *                           "email_verified_at": null,
     *                           "device_token_customer": null,
     *                           "device_token_listener": null,
     *                           "remember_token": null,
     *                           "created_at": "2021-08-11T16:33:32.000000Z",
     *                           "updated_at": "2021-08-11T16:34:18.000000Z",
     *                           "balance": 30,
     *                           "profile_url": null,
     *                           "language": null,
     *                           "stripe_id": null,
     *                           "last_activity_date": null,
     *                           "consent_to_agreement": 0,
     *                           "succesfull_calls": 1,
     *                           "total_time": 50
     *                }},
     *                @OA\Items(
     *
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function index(Request $request): UserResource
    {
        $request->validate([
            'type' => 'nullable|in:customer,listener',
            'order' => 'nullable|in:type,full_name,phone_number,email,rating,created_at,balance,date_of_birth,succesfull_calls,total_time',
            'order_type' => 'nullable|in:asc,desc'
        ]);
        $getDatas = User::query();

        // Filters + Search to query
        if (!is_null($request->type)) {
            $getDatas->where('type', $request->type);
        }
        if (!is_null($request->searchName)) {
            $getDatas->where('full_name', 'like', '%'. $request->searchName .'%');
        }
        if (!is_null($request->searchPhone)) {
            $getDatas->where('phone_number', 'like', '%'. $request->searchPhone .'%');
        }
        if (!is_null($request->searchEmail)) {
            $getDatas->where('email', 'like', '%'. $request->searchEmail .'%');
        }
        $getFinishedDatas = $getDatas->get();
        // Adding additional information about conversation cont and total time spended.
        foreach ($getFinishedDatas as $row) {
            $row->succesfull_calls = (new \App\Models\User)->user_dashboard_succ_calls($row->id);
            $row->total_time = (new \App\Models\User)->user_dashboard_total_time($row->id);
        }

        // Sorting method + acs/desc type.
        if (!is_null($request->order)) {
            if ($request->order_type == 'desc') {
                $getFinishedDatas = $getFinishedDatas->sortByDesc($request->order)->values()->all();
            }
            else {
                $getFinishedDatas = $getFinishedDatas->sortBy($request->order)->values()->all();
            }
        }
        return UserResource::make($getFinishedDatas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return UserResource
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create($request->validated());

        return UserResource::make($user);
    }

    /**
     * @OA\Get (
     *      path="/api/admin-panel/users/{user_id}",
     *      description="Returns datas of current user - > all datas from all resourec (calls,transactions)",
     *      tags={"Admin: users"},
     *      summary="Returns datas of current user - > all datas from all resourec (calls,transactions)",
     *      description="Returns datas of current user - > all datas from all resourec (calls,transactions)",
     *    @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                example={{
     *                          "id": 63,
     *                           "full_name": null,
     *                           "photo": null,
     *                           "email": null,
     *                           "date_of_birth": "06.09.2021",
     *                           "phone_number": "+3712608379423",
     *                           "gender": null,
     *                           "type": "listener",
     *                           "rating": null,
     *                           "status": "offline",
     *                           "bio": null,
     *                           "email_verified_at": null,
     *                           "device_token_customer": null,
     *                           "device_token_listener": null,
     *                           "remember_token": null,
     *                           "created_at": "2021-08-11T16:33:32.000000Z",
     *                           "updated_at": "2021-08-11T16:34:18.000000Z",
     *                           "balance": 30,
     *                           "profile_url": null,
     *                           "language": null,
     *                           "stripe_id": null,
     *                           "last_activity_date": null,
     *                           "consent_to_agreement": 0,
     *                           "succesfull_calls": 1,
     *                           "total_time": 50,
     *                           "conversations_as_listener": {{
     *                              "id": 9,
     *                               "caller_id": 1,
     *                               "listener_id": 63,
     *                               "topic_id": 1,
     *                               "channel": "71iR92aeF8N1osXu",
     *                              "token": "YU8bTTWVuiyqIuHkkymCs2xBQ",
     *                               "started_at": "2021-05-13T05:54:32.000000Z",
     *                               "finished_at": "2021-05-13T05:58:32.000000Z",
     *                               "duration": null,
     *                               "status": "requested",
     *                               "created_at": "2021-07-07T01:31:16.000000Z",
     *                               "updated_at": "2021-07-07T01:31:16.000000Z",
     *                               "check_time": "2021-08-17 16:48:21"
     *                          }},
     *                           "conversations_as_caller": {
     *                           {
     *                           "id": 17,
     *                           "caller_id": 63,
     *                           "listener_id": 64,
     *                           "topic_id": 5,
     *                           "channel": "TWjG2RsdfRAYuZav",
     *                           "token": "7ubgNOymKOygPtC1B9cTt3oix",
     *                           "started_at": "2021-03-27T15:58:23.000000Z",
     *                           "finished_at": null,
     *                           "duration": 50,
     *                           "status": "finished",
     *                           "created_at": "2021-07-07T01:31:16.000000Z",
     *                           "updated_at": "2021-08-19T22:30:02.000000Z",
     *                           "check_time": null
     *                           }
     *                           },
     *                           "transactions": {{
     *                               "id": 30,
     *                               "payment_id": 15,
     *                               "amount": 110,
     *                               "type": "deposit",
     *                               "created_at": "2020-12-24T09:57:38.000000Z",
     *                               "updated_at": "2020-12-24T09:57:38.000000Z",
     *                               "minutes": 11,
     *                               "user_id": null,
     *                               "laravel_through_key": 63
     *                              }}
     *                }},
     *                @OA\Items(
     *
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function show(User $user): UserResource
    {
        $getDatas = User::with('conversationsAsListener','conversationsAsCaller','transactions')->where('id', $user->id)->first();
        $getDatas->succesfull_calls = (new \App\Models\User)->user_dashboard_succ_calls($user->id);
        $getDatas->total_time = (new \App\Models\User)->user_dashboard_total_time($user->id);
        return UserResource::make($getDatas);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProfileRequest $request
     * @param User $user
     * @return UserResource
     */
    public function update(UpdateProfileRequest $request, User $user): UserResource
    {
        if ($request->has('type') && $user->type !== $request->get('type')) {
            event(new UserTypeChanged(user: $user, updatedType: $request->get('type')));
        }

        $user->update($request->validated());

        return UserResource::make($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(User $user): JsonResponse
    {
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
           'success' => true,
           'message' => 'User successfully deleted!',
           'data' => []
        ]);
    }

    /**
     * @return mixed
     */
    private function getPerPageLimit(): int
    {
        return request()->has('per_page') && ($perPage = (int) request()->get('per_page')) > 0
            ? $perPage
            : $this->perPage;
    }
}
