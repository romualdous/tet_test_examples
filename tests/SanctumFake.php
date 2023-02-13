<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Sanctum;

class SanctumFake extends Sanctum
{
    /**
     * Set the current user for the application with the given abilities.
     *
     * @param User $user
     * @param $token
     * @param string $guard
     * @return Authenticatable
     */
    public static function actingAsWithToken(User $user, $token, string $guard = 'sanctum'): Authenticatable
    {
        $user = parent::actingAs($user)->withAccessToken($token);

        app('auth')->guard($guard)->setUser($user);

        return $user;
    }
}
