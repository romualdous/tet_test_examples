<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $date = $this->faker->dateTimeBetween('-234 day');

        return [

            'payer_id' => User::factory()->create(),
            'charge_id' => $this->faker->word(200),
            'payment_intent' => $this->faker->word(200),
            'amount' => $this->faker->numberBetween(7, 200),
            'currency' => 'eur',
            'status' => 'succeeded',
            'created_at' => $date,
            'updated_at' => $date,

        ];
    }
}
