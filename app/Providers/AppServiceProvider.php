<?php

namespace App\Providers;

use App\DataTransferObjects\Contracts\HoldsPaymentData;
use App\DataTransferObjects\StripePayment;
use App\Services\Contracts\SmsService;
use App\Services\Text2ReachService;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public array $bindings = [
        SmsService::class       => Text2ReachService::class,
        HoldsPaymentData::class => StripePayment::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Columns needed are already added to 'users'
         * table in order to have only 'create table' migrations.
         */
        Cashier::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
