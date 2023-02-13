<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\Conversation;
use App\Models\LanguageUser;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use DOMDocument;
use GeneralSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    /**
     * Get current user profile info.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $user->recalculateBalance();
        $user = $user->fresh();

        if($user->type == 'listener'){
            return response()->json([
                'data' => $user->load('spoken_languages', 'topics')
            ]);
        }
        return response()->json([
            'data' => $user->load('spoken_languages')
        ]);
    }

    /**
     * Get data about any user.
     *
     * @param User $user
     * @return UserResource
     */
    public function getUserData(User $user) //: UserResource
    {

        return UserResource::make($user->load('spoken_languages'));
    }

    /**
     * Update current user profile.
     *
     * @param UserUpdateRequest $request
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request)
    {
        $input = $request->all();
        $user = $request->user();

        if ($user->type == 'customer') {
            $request->validate([
                'date_of_birth' => 'date|date_format:d.m.Y|nullable'
            ]);
        }
        else {
            $request->validate([
                'date_of_birth' => 'date|date_format:d.m.Y'
            ]);
        }

        if ($request->delete_image) {

            if ($user->photo) {

                Storage::delete($user->photo);

                $user->photo = null;
                $user->save();
            }
        } else if ($request->has('profile_image')) {

            if ($user->photo) {

                Storage::delete($user->photo);
            }

            //default storage disk is set to 'public' in .env
            $path = config('filesystems.profile_picture_path');

            $image = $request->file('profile_image')->store($path);

            $input = array_merge($request->except(['profile_image', 'spoken_languages']), ['photo' => $request->file('profile_image')->hashName()]);
        }
        if ($request->has('spoken_languages')) {

            $oldLangs = LanguageUser::where('user_id', $user->id)->get();
            foreach ($oldLangs as $lang) {
                $lang->delete();
            }

            foreach ($request->spoken_languages as $lang) {
                $addLang = new LanguageUser();
                $addLang->user_id = $user->id;
                $addLang->language = $lang;
                $addLang->save();
            }
        }
        $user->update($input);
        $user->save();
        return response()->json([
            'data' => $user->load('spoken_languages'),
            'success' => true,
            'message' => 'User successfully updated'
        ]);
    }

    /**
     * Delete current user profile.
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        if (!auth()->user()->delete()) {
            throw new \Exception('There was a problem deleting your profile');
        }
        // tokens() will find all users token and delete them. (Fix -> DIA-78)
        $user = $request->user();
        $user->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'User successfully deleted',
            'data'    => []
        ]);
    }

    /// <summary>
    ///     Method recalculate balance of current user. And save new one in DB.
    /// </summary>
    /// <returns>array</returns>

    /**
     * @OA\Post  (
     *      path="/api/call/recalculatebalance",
     *      description="Recalculates current user's balance.",
     *      tags={"Users"},
     *      summary="Recalculates current user's balance.",
     *      description="Recalculates current user's balance.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="New Balance -> 0"),
     *          )
     *      )
     *    )
     */

    public function recalculateBalance(Request $request): JsonResponse
    {
        return response()->json([
            'data' => 'New Balance -> '. $request->user()->recalculateBalance() //Now i can acces id of user in USER model by $this->id (FIX -> DIA-963)
        ]);
    }

    public function test () {
        $get_idle_time = app(GeneralSettings::class)->max_idle_time;
        $get_time = date('Y-m-d H:i:s', strtotime("-{$get_idle_time} minutes"));

        $getallusers = DB::table('users')
            ->where('type', '=', 'listener')
            ->where('last_activity_date', '<', $get_time)
            ->orWhere('last_activity_date', '=', null)
            ->select('status', 'id')
            ->get();

        foreach ($getallusers as $oneuser)
        {
            DB::update('update users set status = ?, last_activity_date = ? where id = ?', ['offline', null, $oneuser->id]);
        }
    }

    public function test2 () {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://www.1a.lv/ru/p/videokarta-gigabyte-geforce-rtx-3070-aorus-master-rev-2-0-8-gb-gddr6/ct6h?cat=2vs&index=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        $dataFromExternalServer=curl_exec($ch);
        $doc = new DOMDocument();
        $doc->loadHTMLFile("https://www.4games.pro/lv/shop/consoles-and-accessories-lv/microsoft-xbox-accessories-lv/controllers-for-xbox-lv/razer-wolverine-v2/");
        $h1 = $doc->getElementsByTagName("span")->item(0)->textContent;
        dd($h1);
    }

}
