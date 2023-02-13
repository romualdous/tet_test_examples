<?php

namespace Tests\Feature;

use App\Http\Controllers\ConversationController;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private Authenticatable $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    /**
     * @test
     * @dataProvider deviceTokenProvider
     *
     * @param string|null $input
     * @param string|null $expected
     */
    public function it_can_be_set_for_current_user_correctly(?string $input, ?string $expected)
    {
        $this->withoutExceptionHandling();
        $this->postJson(route('device-token.refresh'), ['device_token' => $input, 'type' => $type = 'customer'])->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Device token has been set',
                'data'    => []
            ]);

        $this->assertEquals($expected, $this->user->{"device_token_{$type}"});
    }

    /**
     * @return array
     */
    public function deviceTokenProvider(): array
    {
        return [
            ['very_cool_token', 'very_cool_token'],
            [null, null]
        ];
    }

    /** @test */
    public function correct_firebase_cloud_messaging_instance_gets_selected_depending_on_notification_type()
    {
        $controller = app(ConversationController::class);

        $listenerApiKey = $controller->determineApiKey(['type' => ConversationController::NOTIF_CALL_INCOMING]);
        $this->assertEquals(config('firebase.api_key.listener'), $listenerApiKey);

        $listenerApiKey = $controller->determineApiKey(['type' => ConversationController::NOTIF_CALL_CANCELLED]);
        $this->assertEquals(config('firebase.api_key.listener'), $listenerApiKey);

        $customerApiKey = $controller->determineApiKey(['type' => ConversationController::NOTIF_CALL_FINISHED]);
        $this->assertEquals(config('firebase.api_key.customer'), $customerApiKey);
    }
}
