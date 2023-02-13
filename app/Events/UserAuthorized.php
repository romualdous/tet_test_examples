<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class UserAuthorized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Request
     */
    public Request $request;

    /**
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
