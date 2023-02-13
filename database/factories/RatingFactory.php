<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'full_name'             => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email'                 => $this->faker->safeEmail(),
            'password'              => Hash::make($this->faker->password()),
            'type'                  => $this->faker->randomElement(['customer', 'listener', 'both']),
            'bio'                   => $this->faker->sentence(),
            'date_of_birth'         => $this->faker->dateTimeBetween('1950-01-01')->format('d.m.Y'),
            'gender'                => $this->faker->randomElement(['male', 'female']),
            'phone_number'          => $this->faker->phoneNumber(),
            'balance'               => $this->faker->randomFloat(2),
            'profile_url'           => $this->faker->url(),
            'language'              => $this->faker->languageCode(),
        ];
    }
}
