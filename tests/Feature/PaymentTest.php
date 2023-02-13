<?php

namespace Tests\Feature;

use App\Models\User;
use GeneralSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Tests\TestCase;
use Utils\PaymentMethodClient;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private Authenticatable $user;

    /**
     * Set up basic user.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function fails_if_verification_required()
    {

        $body = [
            'email' => $this->user->email,
            'amount' => 200,
            'currency' => 'eur',
            'payment_method_id' => 'pm_card_authenticationRequired'
        ];

        $response = $this->actingAs($this->user)->post('api/checkout-session', $body);


        $response->assertJson([
            'success' => 0,
            'message' => 'The payment attempt failed because additional action is required before it can be completed.'
        ]);
    }
    /** @test */
    public function fails_if_user_has_no_payment_method()
    {

        $body = [
            'email' => $this->user->email,
            'amount' => 200,
            'currency' => 'eur',
        ];

        $response = $this->actingAs($this->user)->post('api/checkout-session', $body);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => 0,
            'message' => 'User has no payment methods.'
        ]);
    }

    /** @test */
    public function fails_if_user_has_multiple_payment_methods()
    {

        $user = User::factory()->create();

        $user->createOrGetStripeCustomer();

        $user->save();

        $payment_method_id = PaymentMethodClient::createValidPaymentMethod();

        $user->addPaymentMethod($payment_method_id);

        $payment_method_id = PaymentMethodClient::createValidPaymentMethod();

        $user->addPaymentMethod($payment_method_id);

        $body = [
            'email' => $user->email,
            'amount' => 200,
            'currency' => 'eur',
        ];

        $response = $this->actingAs($user)->post('api/checkout-session', $body);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [],
        ]);
    }

    /** @test */
    public function payment_session_can_be_created_with_method_id()
    {

        $user = User::factory()->create();

        $user->createOrGetStripeCustomer();

        $user->save();

        $payment_method_id2 = PaymentMethodClient::createValidPaymentMethod();

        $user->addPaymentMethod($payment_method_id2);

        $body = [
            'email' => $user->email,
            'amount' => 200,
            'currency' => 'eur',
            'payment_method_id' => $payment_method_id2
        ];

        $response = $this->actingAs($user)->post('api/checkout-session', $body);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'client_secret',
                'all_payment_methods',
                'default_payment_method_id',
                'default_payment_method_last4',
            ]
        ]);
    }


    /** @test */
    public function payment_session_fails_validation()
    {

        $body = [
            'email' => 'vojo@gmail.com',
            'amount' => 'sdfgvd',
            'currency' => 'zed',
        ];

        $response = $this->actingAs($this->user)->post('api/checkout-session', $body);

        $response->assertStatus(422);

        $response->assertJson([
            "message" => "The given data was invalid.",
            "errors" => [
                "email" => [
                    "There is no user with this email."
                ],
                "amount" => [
                    "The amount must be an integer."
                ],
                "currency" => [
                    "Given currency is not allowed. Allowed currencies: eur."
                ]
            ]
        ]);
    }


    /** @test */

    public function webhook_updates_user_data()
    {

        $user = User::factory()->create(['stripe_id' => 'cus_JftxMSsi6PGSCh']);

        $oldBalance = $user->balance;

        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [],
                'type' => 'payment_intent.succeeded'
            ]
        ];

        $payload['data']['object'] = array(
            'id' => 'pi_1J2YE3IpFeoeSaiY6rqdq89G',
            'object' => 'payment_intent',
            'amount' => 2000,
            'amount_capturable' => 0,
            'amount_received' => 2000,
            'application' => NULL,
            'canceled_at' => NULL,
            'cancellation_reason' => NULL,
            'capture_method' => 'automatic',
            'charges' =>
            array(
                'object' => 'list',
                'data' =>
                array(
                    0 =>
                    array(
                        'id' => 'ch_1J2YE3IpFeoeSaiYsmC8fOoQ',
                        'object' => 'charge',
                        'amount' => 2000,
                        'amount_captured' => 2000,
                        'amount_refunded' => 0,
                        'application' => NULL,
                        'application_fee' => NULL,
                        'application_fee_amount' => NULL,
                        'balance_transaction' => 'txn_1J2YE4IpFeoeSaiYVOXNorw8',
                        'billing_details' =>
                        array(
                            'address' =>
                            array(
                                'city' => NULL,
                                'country' => NULL,
                                'line1' => NULL,
                                'line2' => NULL,
                                'postal_code' => NULL,
                                'state' => NULL,
                            ),
                            'email' => NULL,
                            'name' => NULL,
                            'phone' => NULL,
                        ),
                        'calculated_statement_descriptor' => 'Stripe',
                        'captured' => true,
                        'created' => 1623747987,
                        'currency' => 'eur',
                        'customer' => 'cus_JftxMSsi6PGSCh',
                        'description' => NULL,
                        'destination' => NULL,
                        'dispute' => NULL,
                        'disputed' => false,
                        'failure_code' => NULL,
                        'failure_message' => NULL,
                        'fraud_details' =>
                        array(),
                        'invoice' => NULL,
                        'livemode' => false,
                        'metadata' =>
                        array(),
                        'on_behalf_of' => NULL,
                        'order' => NULL,
                        'outcome' =>
                        array(
                            'network_status' => 'approved_by_network',
                            'reason' => NULL,
                            'risk_level' => 'normal',
                            'risk_score' => 39,
                            'seller_message' => 'Payment complete.',
                            'type' => 'authorized',
                        ),
                        'paid' => true,
                        'payment_intent' => 'pi_1J2YE3IpFeoeSaiY6rqdq89G',
                        'payment_method' => 'pm_1J2YArIpFeoeSaiY3OYex6Ht',
                        'payment_method_details' =>
                        array(
                            'card' =>
                            array(
                                'brand' => 'visa',
                                'checks' =>
                                array(
                                    'address_line1_check' => NULL,
                                    'address_postal_code_check' => NULL,
                                    'cvc_check' => 'pass',
                                ),
                                'country' => 'US',
                                'exp_month' => 6,
                                'exp_year' => 2022,
                                'fingerprint' => 'GJrfQjVCvAR9QqQw',
                                'funding' => 'credit',
                                'installments' => NULL,
                                'last4' => '4242',
                                'network' => 'visa',
                                'three_d_secure' => NULL,
                                'wallet' => NULL,
                            ),
                            'type' => 'card',
                        ),
                        'receipt_email' => NULL,
                        'receipt_number' => NULL,
                        'receipt_url' => 'https://pay.stripe.com/receipts/acct_1IzZp9IpFeoeSaiY/ch_1J2YE3IpFeoeSaiYsmC8fOoQ/rcpt_Jfu27gVzR0uvQfcWWzi3cuNjuMw290z',
                        'refunded' => false,
                        'refunds' =>
                        array(
                            'object' => 'list',
                            'data' =>
                            array(),
                            'has_more' => false,
                            'total_count' => 0,
                            'url' => '/v1/charges/ch_1J2YE3IpFeoeSaiYsmC8fOoQ/refunds',
                        ),
                        'review' => NULL,
                        'shipping' => NULL,
                        'source' => NULL,
                        'source_transfer' => NULL,
                        'statement_descriptor' => NULL,
                        'statement_descriptor_suffix' => NULL,
                        'status' => 'succeeded',
                        'transfer_data' => NULL,
                        'transfer_group' => NULL,
                    ),
                ),
                'has_more' => false,
                'total_count' => 1,
                'url' => '/v1/charges?payment_intent=pi_1J2YE3IpFeoeSaiY6rqdq89G',
            ),
            'client_secret' => 'pi_1J2YE3IpFeoeSaiY6rqdq89G_secret_fFPOYs5tzNXiBDtDGTrHh4KeH',
            'confirmation_method' => 'automatic',
            'created' => 1623747987,
            'currency' => 'eur',
            'customer' => 'cus_JftxMSsi6PGSCh',
            'description' => NULL,
            'invoice' => NULL,
            'last_payment_error' => NULL,
            'livemode' => false,
            'metadata' =>
            array(),
            'next_action' => NULL,
            'on_behalf_of' => NULL,
            'payment_method' => 'pm_1J2YArIpFeoeSaiY3OYex6Ht',
            'payment_method_options' =>
            array(
                'card' =>
                array(
                    'installments' => NULL,
                    'network' => NULL,
                    'request_three_d_secure' => 'automatic',
                ),
            ),
            'payment_method_types' =>
            array(
                0 => 'card',
            ),
            'receipt_email' => NULL,
            'review' => NULL,
            'setup_future_usage' => 'off_session',
            'shipping' => NULL,
            'source' => NULL,
            'statement_descriptor' => NULL,
            'statement_descriptor_suffix' => NULL,
            'status' => 'succeeded',
            'transfer_data' => NULL,
            'transfer_group' => NULL,
        );

        $response = $this->withoutMiddleware(VerifyWebhookSignature::class)
            ->postJson('stripe/webhook', $payload);

        $upadatedUser = User::find($user->id);

        $callerTimeRate = app(GeneralSettings::class)->caller_time_rate;


        $newBalance = $user->balance + $payload['data']['object']['amount'] / 100 / $callerTimeRate;


        $this->assertEquals($newBalance, $upadatedUser->balance);
    }
}
