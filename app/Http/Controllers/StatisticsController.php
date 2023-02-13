<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatisticsRequest;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class StatisticsController extends Controller
{
    public function getCalls(StatisticsRequest $request)
    {
        $transactions = DB::table('conversations');
        $transactions->select('conversations.*');
        if(!is_null($request->start_date)) $transactions->whereDate('conversations.finished_at', '>=', $request->start_date);
        if(!is_null($request->end_date)) $transactions->whereDate('conversations.finished_at', '<=', $request->end_date);
        $get_transactions = $transactions->get();
        return  response()->json([
            'data' => $get_transactions
        ]);
    }

    public function getTopics()
    {
        $getDatas = Topic::select("id", "title")
            ->withCount('topics')
            ->withCount('topics_finished')
            ->withSum('topics','duration')
            ->get()
            ->sortBy('id')->values()->all();
        return response()->json($getDatas);
    }

    /**
     * @OA\Post(
     *      path="/api/admin-panel/statistics/period",
     *      description="Returns data of calls of time period.",
     *      tags={"Admin Statistics"},
     *      summary="Returns data of calls of time period.",
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(
     *              @OA\Property(property="start_date", type="date", example="2021-07-15"),
     *              @OA\Property(property="end_date", type="date", example="2021-07-16"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="users_count", type="integer", example="51"),
     *              @OA\Property(property="payment_count", type="integer", example="76"),
     *              @OA\Property(property="total_calls", type="integer", example="167"),
     *              @OA\Property(property="total_succesfull_calls", type="integer", example="100"),
     *              @OA\Property(property="total_call_time", type="integer", example="12312451"),
     *          )
     *      ),
     * )
     */

    public function getPeriod(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);
        // Getting users with date
            $getUsers = DB::table('users');
            if(!is_null($request->start_date)) $getUsers->whereDate('users.created_at', '>=', $request->start_date);
            if(!is_null($request->end_date)) $getUsers->whereDate('users.created_at', '<=', $request->end_date);
            $getUserResults = $getUsers->count();

        // Getting payments with date
            $getPayments = DB::table('payments');
            if(!is_null($request->start_date)) $getPayments->whereDate('payments.created_at', '>=', $request->start_date);
            if(!is_null($request->end_date)) $getPayments->whereDate('payments.created_at', '<=', $request->end_date);
            $getPaymentsResults = $getPayments->count();

        // Getting total_calls with date
            $getCalls = DB::table('conversations');
            if(!is_null($request->start_date)) $getCalls->whereDate('conversations.created_at', '>=', $request->start_date);
            if(!is_null($request->end_date)) $getCalls->whereDate('conversations.created_at', '<=', $request->end_date);
            $getCallsResults = $getCalls->count();
            $getCallsFinishedResults = $getCalls->where('status', '=', 'finished')->count();
            $getCallsTotalDuration = $getCalls->where('status', '=', 'finished')->sum('duration');


        return response()->json([
            'users_count' => $getUserResults,
            'payment_count' => $getPaymentsResults,
            'total_calls' => $getCallsResults,
            'total_succesfull_calls' => $getCallsFinishedResults,
            'total_call_time' => $getCallsTotalDuration

        ]);
    }

    /// <summary>
    ///     Method who returns array of data,what includes report datas(conversation data(caller,listener datas)),+ counting how much user got reported alredy.Method accepts different type of filters and sort options.
    /// </summary>
    /// <param name="userMadeReport"></param>
    /// <param name="userGotReported"></param>
    /// <param name="time"></param>
    /// <param name="sort"></param>
    /// <returns>array</returns>

    /**
     * @OA\Get (
     *      path="/api/admin-panel/statistics/reports",
     *      description="Returns all reports datas with different filters",
     *      tags={"Admin Statistics"},
     *      summary="Returns all datas of reports with different filters",
     *      description="Returns all datas of reports with different filters",
     *          @OA\Parameter(
     *          name="sort",
     *          description="Your can provide sort option to all comming datas : 'time' ",
     *          required=false,
     *          example="time",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="userMadeReport",
     *          description="U can provide id of user who made reports. And ull get return datas only with this user.",
     *          required=false,
     *          example="1",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="userGotReported",
     *          description="U can provide id of user who getting reports. And ull get return datas only with this user.",
     *          required=false,
     *          example="2",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="time",
     *          description="U can provide date,in what date u whatnt to see reports.",
     *          required=false,
     *          example="2021-05-28",
     *          in="path",
     *          @OA\Schema(
     *              type="date"
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
     *                           "id": 1,
     *                           "sender": "listener",
     *                           "comment": "test123456",
     *                           "conversation_id": 19,
     *                           "created_at": "2021-08-27T09:42:08.000000Z",
     *                           "updated_at": "2021-08-27T09:42:08.000000Z",
     *                           "got_reported": 1,
     *                           "conversation" : {
     *                                 "id": 19,
     *                                   "caller_id": 64,
     *                                   "listener_id": 55,
     *                                   "topic_id": 3,
     *                                   "channel": "tI2zfvIoVfcAy5r7",
     *                                   "token": "O6KIFE9PZ0JUbpCCIeeQpPJpe",
     *                                   "started_at": "2021-05-20T16:28:28.000000Z",
     *                                   "finished_at": null,
     *                                   "duration": 25,
     *                                   "status": "finished",
     *                                   "created_at": "2021-05-07T01:31:16.000000Z",
     *                                   "updated_at": "2021-08-19T22:30:02.000000Z",
     *                                   "check_time": "2021-08-20 01:22:51",
     *                          "caller": {
     *                               "id": 64,
     *                               "full_name": null,
     *                               "email": null,
     *                               "date_of_birth": "06.09.2021",
     *                               "phone_number": "+37126083794",
     *                               "gender": null,
     *                               "type": "listener",
     *                               "rating": null,
     *                               "status": "offline",
     *                               "bio": null,
     *                               "email_verified_at": null,
     *                               "device_token_customer": null,
     *                               "device_token_listener": null,
     *                               "remember_token": null,
     *                               "created_at": "2021-08-16T13:00:21.000000Z",
     *                               "updated_at": "2021-09-03T10:41:55.000000Z",
     *                               "balance": 404.03,
     *                               "profile_url": null,
     *                               "language": null,
     *                               "stripe_id": null,
     *                              "last_activity_date": "2021-09-01 14:11:18",
     *                               "consent_to_agreement": 0
     *                           },
     *                           "listener": {
     *                               "id": 55,
     *                               "full_name": "Adolph Leuschke",
     *                               "photo": null,
     *                               "email": "marcellus.heathcote@example.org",
     *                               "date_of_birth": "10.12.1999",
     *                               "phone_number": "737.826.0545",
     *                               "gender": "female",
     *                              "type": "listener",
     *                               "rating": null,
     *                               "status": "online",
     *                               "bio": "Illum omnis distinctio maxime error officiis debitis.",
     *                               "email_verified_at": null,
     *                               "device_token_customer": "iQhZqfa37AwxKu8N",
     *                               "device_token_listener": "GBWFnp0aXrZgVEZZ",
     *                               "remember_token": null,
     *                               "created_at": "2021-07-07T01:31:13.000000Z",
     *                               "updated_at": "2021-08-19T22:30:02.000000Z",
     *                               "balance": 478018.66,
     *                               "profile_url": "http://keeling.com/odit-dolore-quia-ea-omnis-laborum-beatae-harum",
     *                               "language": "ce",
     *                               "stripe_id": null,
     *                               "last_activity_date": null,
     *                               "consent_to_agreement": 0
     *                           }
     *                      },
     *                }},
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="sender",
     *                         type="string",
     *                         example="listener"
     *                      ),
     *                      @OA\Property(
     *                         property="comment",
     *                         type="string",
     *                         example="test123456"
     *                      ),
     *                      @OA\Property(
     *                         property="conversation_id",
     *                         type="integer",
     *                         example="102"
     *                      ),
     *                      @OA\Property(
     *                         property="created_at",
     *                         type="datetime",
     *                         example="2021-08-27T09:42:08.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="updated_at",
     *                         type="datetime",
     *                         example="2021-08-27T09:42:08.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="got_reported",
     *                         type="integer",
     *                         example="11"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function getReports(Request $request)
    {
        if (!is_null($request->userMadeReport)) {
            $getDatas = Report::select('reports.*')
                ->join('conversations', 'reports.conversation_id', '=', 'conversations.id')
                ->where([['reports.sender', '=', 'listener'], ['conversations.listener_id', '=', $request->userMadeReport]])
                ->orWhere([['reports.sender', '=', 'caller'], ['conversations.caller_id', '=', $request->userMadeReport]])
                ->get();
        }
        if (!is_null($request->userGotReported)) {
            $getDatas = Report::select('reports.*')
                ->join('conversations', 'reports.conversation_id', '=', 'conversations.id')
                ->where([['reports.sender', '=', 'listener'], ['conversations.caller_id', '=', $request->userGotReported]])
                ->orWhere([['reports.sender', '=', 'caller'], ['conversations.listener_id', '=', $request->userGotReported]])
                ->get();
        }
        if (!is_null($request->time)) {
            $getDatas = Report::select('reports.*')
                ->join('conversations', 'reports.conversation_id', '=', 'conversations.id')
                ->whereDate('reports.created_at', '=', $request->time)
                ->get();
        }
        /* Please provide information,do we need a start_date and end_date ,or just single day. Code as example of alredy completed job.

        if (!is_null($request->start_date) and !is_null($request->end_date)) {
            $getDatas = Report::select('reports.*')
                ->join('conversations', 'reports.conversation_id', '=', 'conversations.id')
                ->whereDate('reports.created_at', '>=', $request->start_date)
                ->whereDate('reports.created_at', '<=', $request->end_date)
                ->get();
        }
        */

        // If no any filter sended to method,then just get all reports with conversation,without any additional condition.
        if(is_null($request->userMadeReport) && is_null($request->time) && is_null($request->userGotReported)) {
            $getDatas = Report::with(['conversation'])
                ->get();
        }
        // Need to check every user his type (caller/listener) and choose what id's ill use future,depends from filters and etc.
        foreach ($getDatas as $get) {
            if ($get->sender == 'caller') {
                if (!is_null($request->userMadeReport)) {
                    if ($get->conversation->caller_id == $request->userMadeReport) {
                        $getUserToCount = $request->userMadeReport;
                    }
                }
                else {
                    $getUserToCount = $get->conversation->listener_id;
                }
            }
            else {
                if (!is_null($request->userMadeReport)) {
                    if ($get->conversation->listener_id == $request->userMadeReport) {
                        $getUserToCount = $request->userMadeReport;
                    }
                }
                else {
                    $getUserToCount = $get->conversation->caller_id;
                }
            }
            // If any of filter exist,and value of filter dont match with value from validation ,then skip this loop. And go for next one.
            if((!is_null($request->userMadeReport) && $getUserToCount != $request->userMadeReport)or(!is_null($request->userGotReported) && $getUserToCount != $request->userGotReported)){
                continue;
            }


            $getReports = DB::table('reports');
            $getReports->join('conversations', 'reports.conversation_id', '=', 'conversations.id');
            $getReports->where([['reports.sender', '=', 'listener'], ['conversations.caller_id', '=', $getUserToCount]]);
            $getReports->orWhere([['reports.sender', '=', 'caller'], ['conversations.listener_id', '=', $getUserToCount],]);
            $get->got_reported = $getReports->count();
        }
        if ($request->sort == 'time') {
            $getDatas = $getDatas->sortByDesc('created_at')->values()->all();
        }
        return response()->json([
            'data' => $getDatas,
        ]);
    }

    /// <summary>
    ///     Method who returns array of data, Can be added many filters ,same as sort options. Both of them got validated,to prevent any error,returns calls lists with caller and listener datas.
    /// </summary>
    /// <param name="status"></param>
    /// <param name="caller"></param>
    /// <param name="listener"></param>
    /// <param name="start_date"></param>
    /// <param name="end_date"></param>
    /// <param name="sort"></param>
    /// <returns>array</returns>

    /**
     * @OA\Get (
     *      path="/api/admin-panel/statistics/calls",
     *      description="Returns all calls datas with different filters",
     *      tags={"Admin Statistics"},
     *      summary="Returns all calls datas with different filters",
     *      description="Returns all calls datas with different filters",
     *          @OA\Parameter(
     *          name="status",
     *          description="Your can provide filter option to all comming datas in: finished,cancelled,on-going,requested",
     *          required=false,
     *          example="requested",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="caller",
     *          description="U can provide id of user. And ull get return datas only with this user in caller role.",
     *          required=false,
     *          example="1",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="listener",
     *          description="U can provide id of user. And ull get return datas only with this user in listener role.",
     *          required=false,
     *          example="2",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="start_date",
     *          description="U can provide start date of all incomming records.",
     *          required=false,
     *          example="2021-05-28",
     *          in="path",
     *          @OA\Schema(
     *              type="date"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="end_date",
     *          description="U can provide end date of all incomming records.",
     *          required=false,
     *          example="2021-05-28",
     *          in="path",
     *          @OA\Schema(
     *              type="date"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="sort",
     *          description="Your can provide sort option to all comming datas in: status,duration,started_at",
     *          required=false,
     *          example="status",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="search",
     *          description="Your can provide search parametr to all comming datas",
     *          required=false,
     *          example="test",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="take",
     *          description="Number for pagination,how much records show per page.(If no value provided,default 5)",
     *          required=false,
     *          example="10",
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *          @OA\Parameter(
     *          name="sort_direction",
     *          description="Choosing sort direction asc or desc.( Accept values in : asc,desc) Works with sort and without",
     *          required=false,
     *          example="asc",
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
     *                example={
     *                           "current_page": 1,
     *                           "data": {{
     *                               "id": 1,
     *                               "caller_id": 31,
     *                               "listener_id": 64,
     *                               "topic_id": 2,
     *                               "channel": "cxXteiDAVatwzPaU",
     *                               "token": "oFCz9oby2cn4atmSOqnMmjupM",
     *                               "started_at": "2022-03-13T01:55:00.000000Z",
     *                               "finished_at": "2022-03-13T01:59:45.000000Z",
     *                               "duration": 4,
     *                               "status": "finished",
     *                               "created_at": "2021-07-07T04:31:16.000000Z",
     *                               "updated_at": "2022-03-13T01:59:45.000000Z",
     *                               "check_time": null,
     *                               "duration_billable": 4,
     *                                       "caller": {
     *                                       "id": 31,
     *                                       "full_name": "Ana Mertz",
     *                                       "photo": null,
     *                                       "email": "phoebe.hahn@example.org",
     *                                       "date_of_birth": "1976-01-10T00:00:00.000000Z",
     *                                       "phone_number": "1-205-543-7623",
     *                                       "gender": "female",
     *                                       "type": "listener",
     *                                       "rating": null,
     *                                       "status": "offline",
     *                                       "bio": "Minus qui sit nulla quidem reprehenderit aut est.",
     *                                       "email_verified_at": null,
     *                                       "device_token_customer": "nIfG5DyPPOIRDcoK",
     *                                       "device_token_listener": "wvaW2y0BdpH4jvaw",
     *                                       "remember_token": null,
     *                                       "created_at": "2021-07-07T04:31:02.000000Z",
     *                                       "updated_at": "2021-07-07T04:31:02.000000Z",
     *                                       "balance": 2964.35,
     *                                       "profile_url": "http://www.gusikowski.info/",
     *                                       "language": "bi",
     *                                       "stripe_id": null,
     *                                       "last_activity_date": null,
     *                                       "consent_to_agreement": 0,
     *                                       "oldenough": 0
     *                                   },
     *                                           "listener": {
     *                                           "id": 64,
     *                                           "full_name": "test123",
     *                                           "photo": "images/profile/Dbd0iqSNANTrQYOJ5zCnYUb77D8BLAfqe91n04KL.png",
     *                                           "email": "roma1239@inbox.lv",
     *                                           "date_of_birth": "1997-01-19T00:00:00.000000Z",
     *                                           "phone_number": "+37126083794",
     *                                           "gender": "male",
     *                                           "type": "customer",
     *                                           "rating": null,
     *                                           "status": "offline",
     *                                           "bio": null,
     *                                           "email_verified_at": null,
     *                                           "device_token_customer": "123123",
     *                                           "device_token_listener": "12344444",
     *                                           "remember_token": null,
     *                                           "created_at": "2021-08-16T16:00:21.000000Z",
     *                                           "updated_at": "2022-03-13T02:03:16.000000Z",
     *                                           "balance": 29,
     *                                           "profile_url": "test2",
     *                                           "language": null,
     *                                           "stripe_id": null,
     *                                           "last_activity_date": null,
     *                                           "consent_to_agreement": 0,
     *                                           "oldenough": 1
     *                                           },
     *                                              "first_page_url": "http://localhost/api/admin-panel/statistics/calls?page=1",
     *                                               "from": 1,
     *                                               "last_page": 2,
     *                                               "last_page_url": "http://localhost/api/admin-panel/statistics/calls?page=2",
     *                                               "links": {
     *                                               {
     *                                               "url": null,
     *                                               "label": "&laquo; Previous",
     *                                               "active": false
     *                                               },
     *                                               {
     *                                               "url": "http://localhost/api/admin-panel/statistics/calls?page=1",
     *                                               "label": "1",
     *                                               "active": true
     *                                               },
     *                                               {
     *                                               "url": "http://localhost/api/admin-panel/statistics/calls?page=2",
     *                                               "label": "2",
     *                                               "active": false
     *                                               },
     *                                               {
     *                                               "url": "http://localhost/api/admin-panel/statistics/calls?page=2",
     *                                               "label": "Next &raquo;",
     *                                               "active": false
     *                                               }
     *                                               },
     *                                               "next_page_url": "http://localhost/api/admin-panel/statistics/calls?page=2",
     *                                               "path": "http://localhost/api/admin-panel/statistics/calls",
     *                                               "per_page": "10",
     *                                               "prev_page_url": null,
     *                                               "to": 10,
     *                                               "total": 16
     *                           }},
     *                },
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="caller_id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="listener_id",
     *                         type="integer",
     *                         example="2"
     *                      ),
     *                      @OA\Property(
     *                         property="topic_id",
     *                         type="integer",
     *                         example="102"
     *                      ),
     *                      @OA\Property(
     *                         property="channel",
     *                         type="string",
     *                         example="cxXteiDAVatwzPaU"
     *                      ),
     *                      @OA\Property(
     *                         property="token",
     *                         type="string",
     *                         example="oFCz9oby2cn4atmSOqnMmjupM"
     *                      ),
     *                      @OA\Property(
     *                         property="started_at",
     *                         type="datetime",
     *                         example="2021-02-24T12:45:50.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="finished_at",
     *                         type="datetime",
     *                         example="null"
     *                      ),
     *                      @OA\Property(
     *                         property="duration",
     *                         type="integer",
     *                         example="null"
     *                      ),
     *                      @OA\Property(
     *                         property="status",
     *                         type="string",
     *                         example="cancelled"
     *                      ),
     *                      @OA\Property(
     *                         property="created_at",
     *                         type="datetime",
     *                         example="2021-07-07T01:31:16.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="updated_at",
     *                         type="datetime",
     *                         example="2021-08-17T13:47:40.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="check_time",
     *                         type="datetime",
     *                         example="2021-02-24 14:55:00"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function getCallsToDashboard(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:finished,cancelled,on-going,requested',
            'sort' => 'nullable|in:status,duration,started_at',
            'search' => 'nullable',
            'take' => 'nullable|numeric',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

// Created query with eloquent model,that can be modyfied futher with additional clauses.
        $getDatas = Conversation::query()->with('caller','listener');
        $getSearchProperty = $request->search;

        if (!is_null($request->status)) {
            $getDatas->where('status', $request->status);
        }
        if (!is_null($request->caller)) {
            $getDatas->where('caller_id', $request->caller);
        }
        if (!is_null($request->listener)) {
            $getDatas->where('listener_id', $request->listener);
        }
        if (!is_null($request->start_date) && !is_null($request->end_date)) {
            $getDatas->whereDate('started_at', '>=', $request->start_date)->whereDate('started_at', '<=', $request->end_date);
        }

        // Search parametr
        if (!is_null($request->search)) {
            // check all callers for search parametr.
            $getDatas->whereHas('caller', function($query) use ($getSearchProperty){
                $query->where('full_name','like','%'.$getSearchProperty.'%')
                    ->orWhere('phone_number','like','%'.$getSearchProperty.'%')
                    ->orWhere('email','like','%'.$getSearchProperty.'%');
            });
            // check all listeners for search parametr.
            $getDatas->orWhereHas('listener', function($query) use ($getSearchProperty){
                $query->where('full_name','like','%'.$getSearchProperty.'%')
                    ->orWhere('phone_number','like','%'.$getSearchProperty.'%')
                    ->orWhere('email','like','%'.$getSearchProperty.'%');
            });
        }
        //Sorting (require sort+sort_direction)
        if (!is_null($request->sort)) {
                if ($request->sort_direction == 'desc') {
                    $getDatas->orderByDesc($request->sort);
                }
                else {
                    $getDatas->orderBy($request->sort);
                }
        }
        else {
            $getDatas->orderBy('id');
        }
// Collecting all datas with filters,what were requested.
        $getFilteredDatas = $getDatas->paginate($request->take ?? 5);

        return response()->json([
            'data' => $getFilteredDatas
        ]);
    }
}
