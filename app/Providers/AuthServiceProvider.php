<?php

namespace App\Providers;

use App\Guards\Providers\AdminProvider;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('admins', function ($app) {
           return new AdminProvider($app['hash'], User::class);
        });

        Auth::extend('admin', function ($app, $name, array $config) {
            return new SessionGuard(
                'admin',
                new AdminProvider($app['hash'],
                    User::class
                ),
                $app['session.store'],
                $app['request']
            );
        });
    }
}
