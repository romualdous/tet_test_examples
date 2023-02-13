<?php

namespace Tests\Feature;

use App\Events\DeviceCreated;
use App\Events\DeviceUserUpdated;
use App\Listeners\TokenInvalidationSubscriber;
use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\SanctumFake;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Authenticatable $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    /** @test */
    public function if_device_exists_it_gets_restored_and_correct_user_is_assigned_to_it()
    {
        Event::fake();

        $softDeletedDevice = Device::factory()->create([
            'user_id'    => User::factory(),
            'deleted_at' => now()
        ]);

        $this->assertTrue($softDeletedDevice->trashed());

        $this->postJson(route('device.store'), $softDeletedDevice->only(['device_id', 'type', 'user_id']))
            ->assertSuccessful()
            ->assertJson([
                'message' => 'Device restored and user ID assigned'
            ]);

        Event::assertDispatched(DeviceUserUpdated::class);
        Event::assertListening(
            DeviceUserUpdated::class,
            [TokenInvalidationSubscriber::class, 'handleDeviceUserUpdated']
        );

        // Pulling fresh device as it got updated
        $device = $softDeletedDevice->fresh();

        $this->assertFalse($device->trashed());
        $this->assertEquals($this->user->id, $device->user_id);
    }

    /** @test */
    public function if_device_does_not_exist_it_gets_created_for_current_user()
    {
        Event::fake();

        $this->assertDatabaseCount('devices', 0);

        $this->postJson(route('device.store'), Device::factory()->make()->toArray())
            ->assertSuccessful()
            ->assertJson([
                'message' => 'Device created successfully'
            ]);

        Event::assertDispatched(DeviceCreated::class);
        Event::assertListening(
            DeviceCreated::class,
            [TokenInvalidationSubscriber::class, 'handleDeviceCreated']
        );

        // Pulling fresh device as it got updated
        $device = Device::first();

        // No matter what user_id gets passed in, currently signed in user gets assigned
        // (since single endpoint is both for create and update operation on device).
        $this->assertEquals($this->user->id, $device->user_id);
    }

    /** @test */
    public function after_device_is_created_appropriate_token_name_is_given_and_other_tokens_get_deleted_along_with_devices()
    {
        $user = User::factory()->create();
        $attributes = [
            'user_id'    => $user->id,
            'device_id'  => 'special',
            'deleted_at' => null
        ];

        $activeDevice = Device::factory()->make($attributes);

        $unusedDevices = Device::factory()
            ->count(4)
            ->create([
                'user_id'    => $user->id,
                'deleted_at' => now()->toDateTimeString()
            ]);

        $otherUserDevice = Device::factory()->create([
            'user_id'    => User::factory(),
            'deleted_at' => null
        ]);

        $unusedToken = $user->createToken('test');

        $user = SanctumFake::actingAsWithToken(
            $user,
            $userToken = $user->createToken('test_token')
        );
        $this->assertAuthenticatedAs($user);
        $this->postJson(route('device.store'), $activeDevice->toArray())->assertSuccessful();

        $this->assertDatabaseHas('devices', $activeDevice->only(['user_id', 'device_id', 'user_agent']));
        $this->assertEquals("user_{$user->id}_device_special", $userToken->accessToken->fresh()->name);

        // Other devices should be deleted
        $unusedDevices->each(fn(Device $device) => $this->assertTrue($device->trashed()));
        $this->assertDatabaseMissing('personal_access_tokens', $unusedToken->accessToken->toArray());

        // Current token and device should stay intact
        $this->assertEquals(
            $activeDevice->only($attributes = ['user_id', 'device_id']),
            $user->getActiveDevice()->only($attributes)
        );
        $this->assertCount(1, $user->devices);
        $this->assertInstanceOf(NewAccessToken::class, $user->currentAccessToken());

        // Other user devices should be intact as well
        $this->assertNotNull($otherUserDevice->fresh());
    }
}
