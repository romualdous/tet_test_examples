<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Http\Resources\TopicCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TopicController extends Controller
{
    /**
     * @OA\Get (
     *      path="/api/topics",
     *      description="Show all topics from database",
     *      tags={"Topics"},
     *      summary="Show all topics from database",
     *      description="Show all topics from database",
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
     *                           "title": "Family",
     *                           "description": "Family description",
     *                           "photo": "family",
     *                }},
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="title",
     *                         type="string",
     *                         example="Family"
     *                      ),
     *                      @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="Family description"
     *                      ),
     *                      @OA\Property(
     *                         property="photo",
     *                         type="string",
     *                         example="family"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function index(): TopicCollection
    {
        return TopicCollection::make(
            Topic::select(['id', 'title', 'description', 'photo'])->get()
        );
    }

    /**
     * @OA\Post  (
     *      path="/api/user-topics",
     *      description="Attach topics to current user.",
     *      tags={"Topics"},
     *      summary="Attach topics to current user.",
     *      description="Attach topics to current user.",
     *    @OA\RequestBody(
     *    required=false,
     *    description="U can provide array of id's what shoud attached to user",
     *    @OA\JsonContent(
     *       @OA\Property(property="topics", type="array", example=
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
     *          @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="true"),
     *             type="object",
     *             @OA\Property(
     *                property="data",
     *                type="array",
     *                example={{
     *                           "id": 1,
     *                           "title": "Family",
     *                           "description": "Family description",
     *                           "photo": "https://via.placeholder.com/640x480.png/000011?text=fuga",
     *                           "created_at": "2021-07-07T01:30:48.000000Z",
     *                           "updated_at": "2021-07-07T01:30:48.000000Z",
     *                }},
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example="1"
     *                      ),
     *                      @OA\Property(
     *                         property="title",
     *                         type="string",
     *                         example="Family"
     *                      ),
     *                      @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="Family description"
     *                      ),
     *                      @OA\Property(
     *                         property="photo",
     *                         type="string",
     *                         example="family"
     *                      ),
     *                      @OA\Property(
     *                         property="created_at",
     *                         type="timestamp",
     *                         example="2021-07-07T01:30:48.000000Z"
     *                      ),
     *                      @OA\Property(
     *                         property="updated_at",
     *                         type="timestamp",
     *                         example="2021-07-07T01:30:48.000000Z"
     *                      ),
     *              )
     *          )
     *      )
     *    )
     * )
     */

    public function attachToUser(Request $request)
    {

        $user = $request->user();

        $user->topics()->detach();

        $user->topics()->attach($request->topics);

        $user->refresh();

        return [
            'success' => true,
            'data' => array_values($user->topics->sort()->toArray())
        ];
    }
}
