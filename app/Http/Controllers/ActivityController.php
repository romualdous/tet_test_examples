<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\Conversation;
use App\Models\Rating;
use App\Models\Topic;
use App\Models\Track;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use GeneralSettings;
use DB;


class ActivityController extends Controller
{
    /**
     * @return UserCollection
     */
    public function index(int $topic_id = null)
    {
        return UserCollection::make(
            $this->getOnlineUsers($topic_id)
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->recordOnlineStatus($request->user());

        return response()->json([]);
    }

    /**
     * Filter listeners by language and topic.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithLanguages(Request $request): JsonResponse
    {



        $users = User::select(['id', 'full_name', 'rating','profile_url','photo','gender','bio','rating'])
            ->topic($request->topic_id)
            ->speaks($request->languages)
            ->listeners()
            ->online()
            ->addSelect([
                'comment' => Rating::select('comment')
                    ->whereColumn('recipient_id', 'users.id')
                    ->latest()
                    ->take(1)
            ])
            ->where('status', '!=', 'on-call')
            ->get();


        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }

    /**
     * @return LengthAwarePaginator
     */
    private function getOnlineUsers(int $topic_id = null): LengthAwarePaginator
    {

        if ($topic_id) {

            return Topic::find($topic_id)
                ->onlineUsers()
                ->paginate((int) config('administrator.pagination.default') ?? 15);
        }

        return User::listeners()
            ->online()
            ->select(['id', 'full_name', 'rating','profile_url','photo','gender','bio','rating'])
            ->where('status', '!=', 'on-call')
            ->addSelect([
                'comment' => Rating::select('comment')
                    ->whereColumn('recipient_id', 'users.id')
                    ->latest()
                    ->take(1)
            ])
            ->paginate((int) config('administrator.pagination.default') ?? 15);

    }

    /**
     * @param Authenticatable|User $user
     *
     * @return mixed
     */
    private function recordOnlineStatus(Authenticatable|User $user): User
    {
        return tap($user)->update(['status' => 'online']);
    }

    ///<summary>
    /// Check if user sitting too long without activity,if yes then set user status offline,and delete his last activity date.
    ///</summary>
    ///<returns>void</returns>
    ///
    public function checkIdleListeners ()
    {
        $get_idle_time = app(GeneralSettings::class)->max_idle_time;
        $get_time = date('Y-m-d H:i:s', strtotime("-{$get_idle_time} minutes"));

        $getallusers = DB::table('users')
            ->where('type', '=', 'listener')
            ->where('last_activity_date', '<', $get_time)
            ->select('status', 'id')
            ->get();

        $getFormatedTime = date("Y-m-d H:i:s", strtotime("now"));
        foreach ($getallusers as $oneuser)
        {
            DB::update('update users set status = ?, last_activity_date = ? where id = ?', ['offline', null, $oneuser->id]);
            //searching if current user,have any track.
            $getTrack = Track::where('end_time', null)->where('user_id', $oneuser->id)->first();
            if (!is_null($getTrack)) {
                $getTrack->end_time = $getFormatedTime;
                $getTrack->save();
            }
        }
    }
}
