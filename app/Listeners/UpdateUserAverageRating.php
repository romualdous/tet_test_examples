<?php

namespace App\Listeners;

use App\Events\RatingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateUserAverageRating
{
    /**
     * Handle the event.
     *
     * @param  RatingCreated  $event
     * @return void
     */
    public function handle(RatingCreated $event)
    {
        $event->recipient->recalculateRating();
    }
}
