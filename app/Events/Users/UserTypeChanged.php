<?php

namespace App\Events\Users;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTypeChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    public User $user;

    /**
     * @var string
     */
    public string $updatedType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, string $updatedType)
    {
        $this->user = $user;
        $this->updatedType = $updatedType;
    }
}
