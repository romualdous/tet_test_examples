<?php

namespace App\Http\Controllers;

use App\Events\UserAuthorized;
use App\Exceptions\Auth\AuthenticationException;
use App\Exceptions\Auth\VerificationException;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyRequest;
use App\Models\Track;
use App\Models\User;
use App\Services\Contracts\SmsService;
use App\Utils\Code;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @var SmsService
     */
    private SmsService $sms;

    /**
     * AuthController constructor.
     */
    public function __construct()
    {
        $this->sms = app(SmsService::class);
    }

    /**
     * @param RegisterRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $typeProvided = $request->get('type');
        $phoneNumber = $request->get('phone_number');
        $platform = $request->get('platform');

        $user = User::firstWhere(['phone_number' => $phoneNumber]);

        // Check for fake LISTENER user.
        $checkFakeUserStatusListener = \config('fake_account.possible_to_use_listener');
        if($checkFakeUserStatusListener == true) {
            if (!$user && $phoneNumber == '+371123456') {
                User::create([
                    'phone_number' => $phoneNumber,
                    'type'         => User::TYPE_LISTENER,
                    'balance'       => 30.00,
                    'verification_code' => Hash::make(123456)
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Testing account created.',
                ]);
            }

            if (!is_null($user) && $phoneNumber == '+371123456') {
                $user->update([
                    'type' => User::TYPE_LISTENER,
                    'verification_code' => Hash::make(123456)
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Testing account set as Listener.',
                ]);
            }
        }
// Check for fake CALLER user.
        $checkFakeUserStatusCustomer = \config('fake_account.possible_to_use_customer');
        if($checkFakeUserStatusCustomer == true) {
            if (!$user && $phoneNumber == '+371654321') {
                User::create([
                    'phone_number' => $phoneNumber,
                    'type'         => User::TYPE_CUSTOMER,
                    'balance'       => 30.00,
                    'verification_code' => Hash::make(654321)
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Customer Testing account created.',
                ]);
            }

            if (!is_null($user) && $phoneNumber == '+371654321') {
                $user->update([
                    'type' => User::TYPE_CUSTOMER,
                    'verification_code' => Hash::make(654321)
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Testing account set as Customer.',
                ]);
            }
        }

        if (!$user && $typeProvided === User::TYPE_LISTENER) {
            throw AuthenticationException::registrationThroughListenerAppIsNotAllowed();
        }

        if ($user && $user->type !== $typeProvided) {
            throw AuthenticationException::typeInRequestDoesNotMatchUserType();
        }

        if (!$user) {
            $user = User::create([
                'phone_number' => $phoneNumber,
                'type'         => User::TYPE_CUSTOMER,
                'balance'       => 30.00
            ]);
        }

        $verificationCode = app(Code::class)->generate();

        $user->update([
            'verification_code' => Hash::make($verificationCode)
        ]);

        $checkDebugKeyStatus = \config('auth_debug_key.possible_to_use_debug_key');
        if(!is_null($request->debug_key) && $request->debug_key == 'asd2e1rljer2r2re' && $checkDebugKeyStatus == true)
        {
            return response()->json([
                'success' => true,
                'message' => 'Code has been sent successfully',
                'verification_code' => $verificationCode
            ]);
        }
        else {
                if ($user->type === User::TYPE_LISTENER) {
                    if ($platform == "android") {
                        $code = str_replace("%CODE%", $verificationCode, env('AUTH_SMS_TEXT_LISTENER_ANDROID'));
                    }
                    else {
                        $code = str_replace("%CODE%", $verificationCode, env('AUTH_SMS_TEXT_LISTENER_IOS'));
                    }
                }
                else {
                    if ($platform == "android") {
                        $code = str_replace("%CODE%", $verificationCode, env('AUTH_SMS_TEXT_CALLER_ANDROID'));
                    }
                    else {
                        $code = str_replace("%CODE%", $verificationCode, env('AUTH_SMS_TEXT_CALLER_IOS'));
                    }
                }

            $this->sms->send($phoneNumber, $code);

            return response()->json([
                'success' => true,
                'message' => 'Code has been sent successfully',
            ]);
        }
        }


    /**
     * @param VerifyRequest $request
     * @return JsonResponse
     * @throws VerificationException
     */
    public function verify(VerifyRequest $request): JsonResponse
    {
        $user = User::firstWhere(
            'phone_number',
            $phoneNumber = $request->phone_number
        );

        if (!$user->verification_code) {
            throw VerificationException::verificationCodeIsNotSet();
        }

        $verificationCorrect = $user->isVerificationCodeCorrect(
            $request->get('verification_code'),
        );

        if (!$verificationCorrect) {
            throw VerificationException::incorrectVerificationCode();
        }

        $user->resetVerificationCode();

        $token = $user->createToken($phoneNumber)->plainTextToken;

        event(new UserAuthorized($user, $request));

        $getTrackData = Track::where('user_id', $user->id)->where('end_time', null)->first();
        $getFormatedTime = date("Y-m-d H:i:s", strtotime("now"));
        if (is_null($getTrackData)) {
            Track::create([
                'user_id' => $user->id,
                'start_time'         => $getFormatedTime,
                'end_time'       => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User successfully verified',
            'data'    => [
                'token' => $token
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        $request
            ->merge(['phone_number' => $phoneNumber = $request->get('phone_number')])
            ->validate(
                [
                    'phone_number' => 'required|string|exists:users,phone_number',
                    'platform' => 'required|string|in:android,ios'
                ]
            );
        $platform = $request->platform;
        $user = User::firstWhere('phone_number', $phoneNumber);
        $newCode = app(Code::class)->generate();
        if ($user->type === User::TYPE_LISTENER) {
            if ($platform == "android") {
                $code = str_replace("%CODE%", $newCode, env('AUTH_SMS_TEXT_LISTENER_ANDROID'));
            }
            else {
                $code = str_replace("%CODE%", $newCode, env('AUTH_SMS_TEXT_LISTENER_IOS'));
            }
        }
        else {
            if ($platform == "android") {
                $code = str_replace("%CODE%", $newCode, env('AUTH_SMS_TEXT_CALLER_ANDROID'));
            }
            else {
                $code = str_replace("%CODE%", $newCode, env('AUTH_SMS_TEXT_CALLER_IOS'));
            }
        }
        $this->sms->send(
            $phoneNumber,
            $code
        );

        $user->forceFill([
            'verification_code' => Hash::make($newCode)
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Verification code resent',
            'data'    => []
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {

        $user = $request->user();
        // tokens() will find all users token and delete them. (Fix -> DIA-78)
        $user->tokens()->delete();
        $user->status = 'offline';
        $user->save();

        $getTrackData = Track::where('user_id', $user->id)->where('end_time', null)->first();
        $getFormatedTime = date("Y-m-d H:i:s", strtotime("now"));
        if (!is_null($getTrackData) && $user->status == 'offline') {
            $getTrackData->end_time = $getFormatedTime;
            $getTrackData->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
            'data'    => []
        ]);
    }
}
