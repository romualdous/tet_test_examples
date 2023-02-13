<?php

namespace App\Providers;

use App\Events\Calls\CallCancelled;
use App\Events\Calls\CallFinished;
use App\Events\Calls\CallOngoing;
use App\Events\Calls\CallRequested;
use App\Events\DeviceCreated;
use App\Events\DeviceUserUpdated;
use App\Events\RatingCreated;
use App\Events\UserAuthorized;
use App\Events\Users\UserTypeChanged;
use App\Listeners\SaveSuccessFullLogin;
use App\Listeners\TokenInvalidationSubscriber;
use App\Listeners\UpdateUserAverageRating;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        UserAuthorized::class => [
            SaveSuccessFullLogin::class
        ],

        CallRequested::class => [
        ],

        CallOngoing::class => [
            //
        ],

        CallCancelled::class => [
            //
        ],

        CallFinished::class => [
            //
        ],

        RatingCreated::class => [
            UpdateUserAverageRating::class
        ],

        UserTypeChanged::class => [
            //
        ],

        DeviceCreated::class => [
            //
        ],

        DeviceUserUpdated::class => [
            //
        ],
    ];

    protected $subscribe = [
        TokenInvalidationSubscriber::class
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
