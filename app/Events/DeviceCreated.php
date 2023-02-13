<?php

namespace App\Events;

use App\Models\Device;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Device
     */
    public Device $device;

    /**
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Device $device, User $user)
    {
        $this->device = $device;
        $this->user = $user;
    }
}
