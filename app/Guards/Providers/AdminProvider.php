<?php

namespace App\Guards\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class AdminProvider extends EloquentUserProvider
{
    /**
     * @param array $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $user = parent::retrieveByCredentials($credentials);

        return $user && $user->hasAbility('admin-access')
            ? $user
            : null;
    }
}
