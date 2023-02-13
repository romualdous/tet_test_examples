<?php

namespace App\Http\Controllers;

use App\Exceptions\UserException;
use App\Models\Track;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws UserException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', User::$availableStatuses)
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! ($user->isListener() || $user->isBothApplicationUser())) {
            throw UserException::userIsNotValidListener();
        }

        $user->update($data);

        $getTrackData = Track::where('user_id', $user->id)->where('end_time', null)->first();
        $getFormatedTime = date("Y-m-d H:i:s", strtotime("now"));
        if (is_null($getTrackData) && $user->status == 'online') {
            Track::create([
                'user_id' => $user->id,
                'start_time'         => $getFormatedTime,
                'end_time'       => null
            ]);
        }
        if (!is_null($getTrackData) && $user->status == 'offline') {
            $getTrackData->end_time = $getFormatedTime;
            $getTrackData->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'User status successfully updated',
            'data'    => []
        ]);
    }
}
