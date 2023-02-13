<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RatingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    public User $recipient;

    /**
     * Create a new event instance.
     *
     * @param User $recipient
     */
    public function __construct(User $recipient)
    {
        $this->recipient = $recipient;
    }
}
