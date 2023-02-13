<?php

namespace App\Listeners;

use App\Events\UserAuthorized;

class SaveSuccessFullLogin
{
    /**
     * Handle the event.
     *
     * @param UserAuthorized $event
     * @return void
     */
    public function handle(UserAuthorized $event)
    {
        $request = $event->request;

        $event->user->logins()->create([
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->userAgent()
        ]);
    }
}
