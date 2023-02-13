<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\UserAuthorized;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ForgotPasswordRequest;
use App\Http\Requests\Dashboard\LoginRequest;
use App\Http\Requests\Dashboard\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Str;

class LoginController extends Controller
{
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        auth()->shouldUse('admin');
    }

    /**
     * Handle an authentication attempt.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function authenticate(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials, (bool) $request->get('remember'))) {
            $request->session()->regenerate();

            event(new UserAuthorized($request->user(), $request));

            return response()->json([
                'success' => true,
                'message' => 'Successfully authenticated!',
                'data'    => []
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'There has been an error while trying to authenticated. Try again.',
            'data'    => []
        ], 404);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out!',
            'data'    => []
        ]);
    }

    /**
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        if (! User::onlyAdmins()->where('email', $request->get('email'))->exists()) {
            return response()->json([
                'success' => false,
                'message' => "User who's password you are trying to request to reset, is not an admin",
                'data'    => []
            ], 404);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                    'success' => true,
                    'message' => 'E-mail with password reset link has been sent!',
                    'data'    => [
                        'email' => $request->get('email')
                    ]
                ]
            );
        }

        return response()->json([
            'success' => false,
            'message' => "There has been an error.",
            'data'    => [
                'email' => __($status)
            ]
        ], 404);
    }

    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => "Password successfully reset!",
                'data'    => []
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "There has been an error.",
            'data'    => [
                'email' => __($status)
            ]
        ], 404);
    }
}
