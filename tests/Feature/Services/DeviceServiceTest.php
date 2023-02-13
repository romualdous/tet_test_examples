<?php

namespace Tests\Feature\Services;

use App\Models\Device;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeviceServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeviceService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new DeviceService();
    }

    /** @test */
    public function it_can_invalidate_all_the_devices_when_no_exceptions_are_given()
    {
        $this->createDevices($count = 30);
        // Method ignores soft deletes, as well as that,
        // currently factory randomizes if to soft-delete
        // the model it is creating.
        $this->assertDatabaseCount('devices', $count);

        $this->service->invalidateAllDevices();

        $this->assertEquals(0, Device::count());
    }

    /** @test */
    public function it_can_invalidate_all_but_the_excepted_devices()
    {
        $devices = $this->createDevices();
        $idsToKeep = $devices->random($amountToKeep = 7)->pluck('device_id')->toArray();

        $this->service->invalidateAllDevices($idsToKeep);

        $this->assertCount(
            $amountToKeep,
            $keptDevices = Device::whereIn('device_id', $idsToKeep)->get()
        );
        $this->assertEquals($amountToKeep, Device::count());

        $devices
            ->filter(function (Device $device) use ($idsToKeep) {
                return ! in_array($device->device_id, $idsToKeep);
            })
            ->each(function (Device $device) {
                $this->assertTrue($device->fresh()->trashed());
            });

        $keptDevices
            ->each(function (Device $device) use ($idsToKeep) {
                $this->assertTrue(in_array($device->device_id, $idsToKeep));
            });
    }

    /** @test */
    public function it_can_invalidate_all_user_devices_if_no_exceptions_are_given()
    {
        $user = User::factory()->create();
        $this->createDevices($count = 10, ['user_id' => $user->id, 'deleted_at' => null]);

        $this->assertcount($count, $user->devices);
        $this->service->invalidateUserDevices($user);
        $this->assertCount(0, $user->fresh()->devices);

        // Devices are soft-deleted, therefore, physically they are in DB
        $this->assertDatabaseCount('devices', $count);
    }

    /** @test */
    public function it_can_invalidate_all_but_the_excepted_devices_for_given_user()
    {
        $user = User::factory()->create();
        $devices = $this->createDevices(params: ['user_id' => $user->id, 'deleted_at' => null]);
        $idsToKeep = $devices->random($amountToKeep = 7)->pluck('device_id')->toArray();

        $this->service->invalidateUserDevices($user, $idsToKeep);

        $this->assertCount(
            $amountToKeep,
            $keptDevices = Device::whereIn('device_id', $idsToKeep)->get()
        );

        $keptDevices
            ->each(function (Device $device) use ($user, $idsToKeep) {
                $this->assertTrue(in_array($device->device_id, $idsToKeep));
                $this->assertEquals($user->id, $device->user_id);
            });
    }

    /** @test */
    public function it_can_delete_the_tokens_associated_with_devices()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $devices = $this->createDevices(10, ['user_id' => $user->id, 'deleted_at' => null]);

        $devices->each(function (Device $device) use ($user) {
            $user->createToken($user->generateTokenName($device->device_id));
        });

        $this->service->invalidateAssociatedTokens(
            $user,
            $devices->pluck('device_id')->toArray()
        );

        $this->assertCount(0, $user->fresh()->tokens);
    }

    private function createDevices(int $count = 30, array $params = []): Collection|Device
    {
        return Device::factory()
            ->count($count)
            ->create($params ?: ['deleted_at' => null]);
    }
}
