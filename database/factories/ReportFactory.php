<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'sender' => $this->faker->randomElement(['caller', 'listener']),
            'comment' => $this->faker->realText([3000]),
            'conversation_id' => Conversation::factory()->create(),

        ];
    }
}
