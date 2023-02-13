<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $date = $this->faker->dateTimeBetween('-234 day');
        return [
            'caller_id'   => User::factory()->create(['type' => 'customer']),
            'listener_id' => User::factory()->create(['type' => 'listener']),
            'topic_id'    => null,
            'channel'     => Str::random(),
            'token'       => Str::random(25),
            'started_at'  => $date,
            'finished_at' => Carbon::parse($date)->addMinutes(4),
            'duration'    => $this->faker->numberBetween(7, 200),
            'status'      => Conversation::STATUS_REQUESTED
        ];
    }
}
