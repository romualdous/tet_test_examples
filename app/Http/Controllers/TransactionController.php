<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\TransactionRequest;

class TransactionController extends Controller
{
    public function getConversations(TransactionRequest $request)
    {
        /**
         * @OA\Get (
         *      path="/api/transactions",
         *      description="Show all transactions with current user",
         *      tags={"Transactions"},
         *      summary="Show all transactions with current user",
         *      description="Show all transactions with current user,can be filtered with dates.",
         *          @OA\Parameter(
         *          name="start_date",
         *          description="Start date for filter. From where to start to search.",
         *          required=false,
         *          example="2021-05-24",
         *          in="path",
         *          @OA\Schema(
         *              type="date"
         *          )
         *      ),
         *          @OA\Parameter(
         *          name="end_date",
         *          description="End date of filter.",
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
         *                           "date": "2021-05-29 13:08:08",
         *                           "type": "Earn",
         *                           "amount": null,
         *                           "minutes": 102,
         *                }},
         *                @OA\Items(
         *                      @OA\Property(
         *                         property="date",
         *                         type="datetime",
         *                         example="2021-05-29 13:08:08"
         *                      ),
         *                      @OA\Property(
         *                         property="type",
         *                         type="string",
         *                         example="Earn"
         *                      ),
         *                      @OA\Property(
         *                         property="amount",
         *                         type="biginteger",
         *                         example="null"
         *                      ),
         *                      @OA\Property(
         *                         property="minutes",
         *                         type="double",
         *                         example="102"
         *                      ),
         *              )
         *          )
         *      )
         *    )
         * )
         */

        return response()->json([
            'data' =>  $request->user()->getTransactions($request->start_date,$request->end_date)
        ]);
    }
}
