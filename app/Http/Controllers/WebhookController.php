<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use GeneralSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;



class WebhookController extends CashierWebhookController
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handlePaymentIntentSucceeded($payload)
    {


        $paymentData = $payload['data']['object'];

        $payer = User::where('stripe_id', '=', $paymentData['customer'])->first();

        $callerTimeRate = app(GeneralSettings::class)->caller_time_rate;

        DB::transaction(function () use ($paymentData, $payer, $callerTimeRate) {

            $payment = Payment::create([
                'charge_id' => $paymentData['charges']['data'][0]['id'],
                'payment_intent' => $paymentData['id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'status' => $paymentData['status'],
                'payer_id' => $payer->id

            ]);

            $transaction = Transaction::create([

                'amount' => $paymentData['amount'],
                'minutes' => $paymentData['amount'] / 100 / $callerTimeRate,
                'payment_id' => $payment->id,
                'type' => 'deposit',
                'user_id' => $payer->id

            ]);

            //TODO change model create not to use fillable


            $payer->balance += $paymentData['amount'] / 100 / $callerTimeRate;

            $payer->save();

            return response('Webhook executed', 200);
        });
    }
}
