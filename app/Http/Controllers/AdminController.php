<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    /// <summary>
    ///     Method change choosed user type listener <-> customer.
    /// </summary>
    /// <param name="type"></param>
    /// <returns>array</returns>

    /**
     * @OA\Post(
     *      path="/api/admin-panel/users/{user_id}",
     *      description="Change current user type(listener,customer)",
     *      tags={"Admin: users"},
     *      summary="Change current user type(listener,customer)",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"type"},
     *              @OA\Property(property="type", type="string", example="listener"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="string", example="Type is changed"),
     *          )
     *      ),
     * )
     */

    public function editUsersById(Request $request,$user)
    {
        $request->validate([
            'type' => 'required|in:listener,customer'
        ]);
        $getDatas = User::where('id', $user)->first();
        $getDatas->type = $request->type;
        $getDatas->save();
        return response()->json([
            'data' => 'Type is changed'
        ]);

    }

    /// <summary>
    ///     Method withdraw amount from balance. Have check for empty or negarive balance + creating transaction about withdraw.
    /// </summary>
    /// <param name="amount"></param>
    /// <param name="minutes"></param>
    /// <returns>array</returns>

    /**
     * @OA\Post(
     *      path="/api/admin-panel/users/{user_id}/withdraw",
     *      description="Withdraw amount of money from users balance with checks. ( AMOUNT IS IN CENTS )",
     *      tags={"Admin: users"},
     *      summary="Withdraw amount of money from users balance with checks. ( AMOUNT IS IN CENTS )",
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(
     *              @OA\Property(property="amount", type="integer", example="1234"),
     *              @OA\Property(property="minutes", type="integer", example="1234"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="string", example="Withdraw succesfull."),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Low balance",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="string", example="Your balance lower,then withdraw amount."),
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="No withdraw type provided",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="string", example="Please provide amount or minutes to withdraw."),
     *          )
     *      ),
     * )
     */

    public function withdrawFromUser(Request $request,$user)
    {
        $request->validate([
            'amount' => 'nullable|numeric',
            'minutes' => 'nullable|numeric'
        ]);
        if (is_null($request->amount) && is_null($request->minutes)) {
            return response()->json([
                'data' => 'Please provide amount or minutes to withdraw'
            ],403);
        }
        $getUser = User::where('id', $user)->first();
        $callerTimeRate = app(GeneralSettings::class)->caller_time_rate;
        // Depending what type of withdraw got from request,calcaulting amount and minutes.
        if ($request->amount) {
            $getMinutes = $request->amount / 100 / $callerTimeRate;
            $getAmount = $request->amount;
        }
        else {
            $getAmount = $request->minutes * 100 * $callerTimeRate;
            $getMinutes = $request->minutes;
        }
        $getBalance = $getUser->balance;
        if ($getBalance < $getMinutes) {
            return response()->json([
                'data' => 'Your balance lower,then withdraw amount.'
            ], 400);
        }
        Transaction::create([
            'amount' => $getAmount,
            'type'         => 'withdraw',
            'created_at'       => date('Y-m-d H:i:s', strtotime(now())),
            'user_id' => $user,
            'minutes' => $getMinutes,
        ]);
        // Calculating new balance,and save it to user
        $getNewBalance = $getBalance - $getMinutes;
        $getUser->balance = $getNewBalance;
        $getUser->save();

        return response()->json([
            'data' => 'Withdraw succesfull.'
        ]);
    }

    /// <summary>
    ///     Method recalculate balance of user. And save new one in DB.
    /// </summary>
    /// <returns>array</returns>

    /**
     * @OA\Post  (
     *      path="/api/admin-panel/users/{id}/recalculatebalance",
     *      description="Recalculates user's balance from route by id.",
     *      tags={"Admin: users"},
     *      summary="Recalculates user's balance from route by id",
     *      description="Recalculates user's balance from route by id",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="New Balance -> 0"),
     *          )
     *      )
     *    )
     */

    public function recalculateBalanceOnUser($user_id)
    {
        $user = User::where('id', $user_id)->first();
        return response()->json([
            'data' => 'New Balance -> '.$user->recalculateBalance()
        ]);
    }

    /**
     * @OA\Post  (
     *      path="/api/admin-panel/users/tracking/all",
     *      description="Track users online time with propertys",
     *      tags={"Admin: users"},
     *      summary="Track users online time with propertys",
     *      description="Track users online time with propertys",
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"start_time"},
     *              @OA\Property(property="start_time", type="date", example="2022-01-01"),
     *              @OA\Property(property="end_time", type="date", example="2022-01-03"),
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
     *                           "Name": "Vojislav Pavasović",
     *                           "Phone": "+37126083794",
     *                           "Time_Online": "4",
     *                }},
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="Name",
     *                         type="string",
     *                         example="Vojislav Pavasović"
     *                      ),
     *                      @OA\Property(
     *                         property="Phone",
     *                         type="string",
     *                         example="+37126083794"
     *                      ),
     *                      @OA\Property(
     *                         property="Time_Online",
     *                         type="integer",
     *                         example="4"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     *    )
     */

    public function tracking(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date'
        ]);

        $array = [];
        $allTracks = DB::table('tracks');
        $allTracks->join('users', 'tracks.user_id', '=', 'users.id');
        $allTracks->select('users.full_name','users.phone_number','tracks.start_time','tracks.end_time','users.id');
        $allTracks->where('end_time', '!=', null);
        $allTracks->where('users.type', '=', 'listener');
        $allTracks->whereDate('start_time', '>=', $request->start_time);
        if(!is_null($request->end_time)) $allTracks->whereDate('end_time', '<=', $request->end_time);
        $get_allTracks = $allTracks->get();

        foreach($get_allTracks as $sub) {
            if(!isset($array[$sub->id])) {
                $array[$sub->id] = array('id' => $sub->id,
                    'Name' => $sub->full_name,
                    'Phone' => $sub->phone_number,
                    'Time_Online' => Carbon::parse($sub->start_time)->diffInMinutes(Carbon::parse($sub->end_time)));
            } else {
                $array[$sub->id]['Time_Online'] += Carbon::parse($sub->start_time)->diffInMinutes(Carbon::parse($sub->end_time));
            }
        }
        // Temp solution to fix data response.
        foreach ($array as $arr) {
            $temp_arr = [];
            $temp_arr['id'] = $arr['id'];
            $temp_arr['Name'] = $arr['Name'];
            $temp_arr['Phone'] = $arr['Phone'];
            $temp_arr['Time_Online'] = $arr['Time_Online'];
            $finished_array[] = $temp_arr;
        }

        return response()->json([
            'data' => $finished_array
        ]);
    }
    /**
     * @OA\Post  (
     *      path="/api/admin-panel/users/{user_id}/tracking",
     *      description="Show current users Tracks",
     *      tags={"Admin: users"},
     *      summary="Show current users Tracks",
     *      description="Show current users Tracks",
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"start_time"},
     *              @OA\Property(property="start_time", type="date", example="2022-01-01"),
     *              @OA\Property(property="end_time", type="date", example="2022-01-03"),
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
     *                           "full_name": "test123",
     *                           "phone_number": "+37126083794",
     *                           "start_time": "2022-01-11 15:39:21",
     *                           "end_time": "2022-01-12 02:06:45",
     *                }},
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="full_name",
     *                         type="string",
     *                         example="test123"
     *                      ),
     *                      @OA\Property(
     *                         property="phone_number",
     *                         type="string",
     *                         example="+37126083794"
     *                      ),
     *                      @OA\Property(
     *                         property="start_time",
     *                         type="datetime",
     *                         example="2022-01-11 15:39:21"
     *                      ),
     *                      @OA\Property(
     *                         property="end_time",
     *                         type="datetime",
     *                         example="2022-01-12 02:06:45"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     *    )
     */

    public function useridTracking($user_id,Request $request)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date'
        ]);

        $allTracks = DB::table('tracks');
        $allTracks->join('users', 'tracks.user_id', '=', 'users.id');
        $allTracks->select('users.full_name','users.phone_number','tracks.start_time','tracks.end_time');
        $allTracks->whereDate('start_time', '>=', $request->start_time);
        if(!is_null($request->end_time)) $allTracks->whereDate('end_time', '<=', $request->end_time);
        $allTracks->where('user_id', $user_id);
        $get_allTracks = $allTracks->get();
        return response()->json([
            'data' => $get_allTracks
        ]);
    }
}
