<?php

namespace Utils;

use GuzzleHttp\Client;

class PaymentMethodClient
{

    /**
     * Create valid Stripe payment method.
     *
     */
    public static function createValidPaymentMethod(): string
    {

        $client = new Client();

        $res = $client->post('https://api.stripe.com/v1/payment_methods', [
            'auth' => ['sk_test_51IzZp9IpFeoeSaiY9xU6kLI6XiRuwmcMgbVPR7pW43PNppSoe5oGvQrDTiXM0cizwVoznhe1M8RjZeQaqBuqZ1Y300j0xjQUP5', ''],
            'form_params' => [
                'type' => 'card',
                'card' => [
                    'number' => 4242424242424242,
                    'exp_month' => 6,
                    'exp_year' => 2022,
                    'cvc' => 314
                ]
            ]
        ]);

        $resdata = json_decode($res->getBody());
        $payment_method_id = $resdata->id;

        return $payment_method_id;
    }
}
