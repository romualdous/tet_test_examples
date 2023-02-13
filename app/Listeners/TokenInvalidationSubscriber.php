<?php

namespace App\Listeners;

use App\Events\DeviceCreated;
use App\Events\DeviceUserUpdated;
use App\Events\Users\UserTypeChanged;
use App\Models\User;
use App\Services\DeviceService;

class TokenInvalidationSubscriber
{
    /**
     * @var DeviceService
     */
    private DeviceService $service;

    /**
     * TokenInvalidationSubscriber constructor.
     * @param DeviceService $service
     */
    public function __construct(DeviceService $service)
    {
        $this->service = $service;
    }

    /**
     * Register different event listener methods.
     *
     * @param $events
     */
    public function subscribe($events)
    {
        $events->listen(
            UserTypeChanged::class,
            [self::class, 'handleUserTypeChanged']
        );

        $events->listen(
            DeviceCreated::class,
            [self::class, 'handleDeviceCreated']
        );

        $events->listen(
            DeviceUserUpdated::class,
            [self::class, 'handleDeviceUserUpdated']
        );
    }

    /**
     * @param $event
     */
    public function handleUserTypeChanged($event)
    {
        /** @var User $user */
        $user = $event->user;

        if ($user->hasAbility('admin-access')) {
            // Since administrators get their session de-authorization
            // handled differently than normal app users,
            // we won't be looking to de-authorize their mobile devices,
            // since admin-dashboard won't be consumed by mobile *app*
            // as their sessions are handle with session cookies and
            // Laravel already has a mechanism to do de-auth with session cookies.
            return;
        }

        $this->invalidateOtherDevices(
            $event->user,
            [$event->user->getActiveDevice()->device_id]
        );
    }

    /**
     * @param $event
     */
    public function handleDeviceCreated($event)
    {
        $this->associateDeviceWithToken($event->user);

        $this->invalidateOtherDevices(
            $event->user,
            [$event->device->device_id]
        );
    }

    /**
     * @param $event
     */
    public function handleDeviceUserUpdated($event)
    {
        $this->invalidateOtherDevices(
            $event->device->user,
            [$event->device->device_id]
        );
    }

    /**
     * @param User $user
     * @param array $except
     */
    private function invalidateOtherDevices(User $user, array $except): void
    {
        $this->service->invalidateUserDevices($user, $except);
    }

    public function associateDeviceWithToken(User $user)
    {
        $user->currentAccessToken()->accessToken->update([
            'name' => $user->generateTokenName($user->getActiveDevice()->device_id)
        ]);
    }
}
