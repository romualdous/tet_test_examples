<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\CheckoutSessionRequest;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Laravel\Cashier\Exceptions\PaymentFailure;

class PaymentController extends Controller
{
    /**
     * Initialize Stripe payment session.
     *
     * @param CheckoutSessionRequest $request
     * @return JsonResponse
     * @throws PaymentActionRequired
     * @throws PaymentFailure
     */
    public function createPaymentSession(CheckoutSessionRequest $request): JsonResponse
    {

        $user = $request->user();

        $user->createOrGetStripeCustomer();

        $user->save();

        if ($request->has('payment_method_id')) {

            $user->addPaymentMethod($request->payment_method_id);

            $user->updateDefaultPaymentMethod($request->payment_method_id);


            $charge = $user->charge(
                amount: $request->get('amount'),
                paymentMethod: $user->defaultPaymentMethod()->id,
                options: [
                    'setup_future_usage' => 'off_session',
                    'currency' => $request->get('currency')
                ]
            );

            return response()->json([
                'success' => 1,
                'data' => [
                    'client_secret'  => $charge->client_secret,
                    'all_payment_methods' => $user->paymentMethods()->toArray(),
                    'default_payment_method_id' => $user->defaultPaymentMethod()->id,
                    'default_payment_method_last4' => $user->defaultPaymentMethod()->card->last4
                ]
            ]);
        } else {


            if ($user->hasPaymentMethod()) {



                return response()->json([
                    'success' => 0,
                    'message' => 'Choose payment method',
                    'data' => $user->paymentMethods()->toArray(),
                ]);
            }
            if (!$user->hasPaymentMethod()) {

                return response()->json([
                    'success' => 0,
                    'message' => 'User has no payment methods.'

                ]);
            }
        }
    }
}
