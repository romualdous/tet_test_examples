<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use GeneralSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = $this->faker->dateTimeBetween('-234 day');

        return [

            'payment_id' => Payment::factory(),
            'amount' => $this->faker->numberBetween(7, 200),
            'minutes' => function (array $attributes) {
                return $attributes['amount'] / 100 / app(GeneralSettings::class)->caller_time_rate;
            },
            'type' => 'deposit',
            'created_at' => $date,
            'updated_at' => $date,

        ];
    }
}
