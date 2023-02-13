<?php

namespace Tests\Feature\Auth;

use App\Events\UserAuthorized;
use App\Exceptions\Auth\AuthenticationException;
use App\Exceptions\Auth\VerificationException;
use App\Models\Device;
use App\Models\User;
use App\Services\Text2ReachService;
use App\Utils\Code;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhoneAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var int
     */
    private int $verificationCode;

    public function setUp(): void
    {
        parent::setUp();

        $this->verificationCode = 111111;

        $this->mock(Code::class)
            ->shouldReceive('generate')
            ->withNoArgs()
            ->andReturn($this->verificationCode);

        $this->mock(Text2ReachService::class)
            ->shouldReceive('send')
            ->with('+37129179850', $this->verificationCode)
            ->andReturn(true);
    }

    /** @test */
    public function blank_user_with_verification_code_should_be_in_database_when_register_route_gets_hit_successfully()
    {
        $this->postJson(route('login'), [
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => User::TYPE_CUSTOMER
        ])->assertSuccessful();

        $user = User::first();

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check($this->verificationCode, $user->verification_code));
        $this->assertEquals($phoneNumber, $user->phone_number);
    }

    /** @test */
    public function sending_phone_number_without_country_code_will_fail_the_validation()
    {
        $this->postJson(route('login'), [
            'phone_number' => '29179850',
            'type'         => User::TYPE_CUSTOMER
        ])->assertStatus(422)
            ->assertJsonValidationErrors('phone_number');

        $this->assertDatabaseCount('users', 0);
    }

    /** @test */
    public function successfully_verified_user_should_get_access_token()
    {
        $this->postJson(route('login'), [
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => User::TYPE_CUSTOMER
        ]);

        $this->assignDevice($user = User::first());

        $this->postJson(route('verify'), [
            'phone_number'      => $phoneNumber,
            'verification_code' => $this->verificationCode
        ]);

        $user = $user->fresh();

        $this->assertInstanceOf(PersonalAccessToken::class, $user->tokens()->first());
        $this->assertCount(1, $user->tokens);
        $this->assertCount(1, $user->logins);
    }

    /** @test */
    public function user_can_successfully_log_out()
    {
        Sanctum::actingAs($user = User::factory()->create(['phone_number' => '+37129179850']));

        $response = $this->postJson(route('logout'));

        $response->assertJson([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);

        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function user_without_set_verification_code_cannot_attempt_to_verify_his_phone_number()
    {
        // Apparently, if exception is caught in try/catch,
        // it doesn't register in $this->expectException($e::class) method
        $this->withoutExceptionHandling();
        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('Verification code has not been set');

        User::factory()->create([
            'phone_number'      => $phoneNumber = '+37129179850',
            'verification_code' => null
        ]);

        $this->postJson(route('verify'), [
            'phone_number'      => $phoneNumber,
            'verification_code' => 123456
        ]);
    }

    /** @test */
    public function already_authenticated_user_can_access_register_route_due_to_app_data_loss_or_similar()
    {
        Sanctum::actingAs(User::factory()->create([
            'phone_number'      => $phoneNumber = '+37129179850',
            'type'              => User::TYPE_CUSTOMER,
            'verification_code' => null
        ]), ['*']);

        $this->assertTrue(auth()->guard('sanctum')->check());

        $response = $this->postJson(route('login'), [
            'phone_number' => $phoneNumber,
            'type'         => User::TYPE_CUSTOMER
        ]);

        $response->assertJson([
            "message" => "Code has been sent successfully"
        ]);
    }

    /** @test */
    public function user_can_successfully_login_after_resending_code()
    {
        $this->postJson(route('login'), [
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => User::TYPE_CUSTOMER
        ]);

        $user = User::first();

        $this->assertNotNull($initialCode = $user->verification_code);

        $this->postJson(route('resend'), [
            'phone_number' => $phoneNumber
        ]);

        $user = $user->fresh();

        $this->assertNotEquals($initialCode, $user->verification_code);

        $this->postJson(route('verify'), [
            'phone_number'      => $phoneNumber,
            'verification_code' => $this->verificationCode
        ])->assertSuccessful()->assertJson([
            'success' => true,
            'message' => 'User successfully verified'
        ]);
    }

    /** @test */
    public function if_user_is_found_but_type_in_request_does_not_match_user_type_exception_gets_thrown()
    {
        $this->withoutExceptionHandling();

        User::factory()->create([
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => User::TYPE_CUSTOMER
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectDeprecationMessage(AuthenticationException::typeInRequestDoesNotMatchUserType()->getMessage());

        $this->postJson(route('login'), [
            'phone_number' => $phoneNumber,
            'type'         => 'listener'
        ]);
    }

    /** @test */
    public function newly_registered_users_get_assigned_customer_type()
    {
        $this->withoutExceptionHandling();
        $this->postJson(route('login'), [
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => User::TYPE_CUSTOMER
        ])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertEquals(User::TYPE_CUSTOMER, User::firstWhere(['phone_number' => $phoneNumber])->type);
    }

    /** @test */
    public function user_cannot_register_as_a_listener_from_listener_application()
    {
        $this->withoutExceptionHandling();
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(AuthenticationException::registrationThroughListenerAppIsNotAllowed()->getMessage());

        $this->postJson(route('login'), [
            'phone_number' => '+37129179850',
            'type'         => User::TYPE_LISTENER
        ]);
    }

    /** @test */
    public function listener_can_log_into_listener_application_if_there_is_an_account_for_given_phone_number()
    {
        $attributes = [
            'phone_number' => '+37129179850',
            'type'         => User::TYPE_LISTENER
        ];

        // With a listener user already in the system for the given phone number,
        // listener can successfully log into his/her account.
        User::factory()->create($attributes);

        $this->postJson(route('login'), $attributes)->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider phoneNumberProvider
     */
    public function any_baltic_states_phone_number_is_valid_number_for_registration($phoneNumber)
    {
        $this->mock(Text2ReachService::class)
            ->shouldReceive('send')
            ->withAnyArgs()
            ->andReturn(true);

        $attributes = [
            'phone_number' => $phoneNumber,
            'type'         => User::TYPE_CUSTOMER
        ];

        $this->postJson(route('login'), $attributes)
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
    }

    public function phoneNumberProvider(): array
    {
        return [
            ['+37123948656'],
            ['+37023948656'],
            ['+37223948656'],
        ];
    }

    /**
     * @param User|null $user
     * @return Device|null
     */
    private function assignDevice(?User $user = null): ?Device
    {
        return Device::factory()->create(['user_id' => $user ? $user->id : null]);
    }
}
