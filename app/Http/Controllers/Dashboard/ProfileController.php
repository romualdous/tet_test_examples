<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        auth()->shouldUse('admin');
    }

    /**
     * Show profile information.
     *
     * @return ProfileResource
     */
    public function index(): ProfileResource
    {
        return ProfileResource::make(auth()->user());
    }

    /**
     * Change password for current user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user('admin');

        $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) use ($request, $user) {
                    if (! Hash::check($value, $user->password)) {
                        $fail('Password given does not match current password.');
                    }
                }
            ],
            'new_password'     => 'required|min:3|confirmed'
        ]);

        $user->forceFill([
            'password' => Hash::make($request->get('new_password'))
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully updated password!',
            'data'    => []
        ]);
    }

    /**
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateInfo(UpdateProfileRequest $request): JsonResponse
    {
        $request->user('admin')->update(
            $request->only(['full_name'])
        );

        $request->whenHas('photo', function ($photo) use ($request) {
            $this->uploadPhotoFor($request->user('admin'), $photo);
        });

        return response()->json([
            'success' => true,
            'message' => 'Profile has been successfully updated!',
            'data'    => []
        ]);
    }

    /**
     * Delete currently signed-in admin photo
     * and replace it with custom photo.
     * @param Request $request
     * @return mixed
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $request->user('admin')->resetProfileImage();

        return response()->json([
            'success' => true,
            'message' => 'Profile photo successfully deleted!',
            'data'    => []
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user('admin');

        $request->validate([
            'password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (! Hash::check($value, $user->password)) {
                        $fail('Password given does not match current password.');
                    }
                }
            ]
        ]);

        Auth::logoutOtherDevices($request->get('password'));

        return response()->json([
            'success' => true,
            'message' => 'Other devices have been unauthorized successfully!',
            'data'    => []
        ]);
    }

    /**
     * @param User $user
     * @param UploadedFile $file
     * @return JsonResponse|Media
     */
    private function uploadPhotoFor(User $user, UploadedFile $file): JsonResponse|Media
    {
        try {
            $photo = $user->addMedia($file)->toMediaCollection('photo');
            $user->update(['photo' => $photo->getFullUrl()]);
        } catch (FileIsTooBig | FileDoesNotExist $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ]);
        }

        return $photo;
    }
}
