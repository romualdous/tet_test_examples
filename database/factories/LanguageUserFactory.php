<?php

namespace Database\Factories;

use App\Models\LanguageUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LanguageUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'language' => $this->faker->randomElement(['lv', 'ru', 'en'])
        ];
    }
}
