<?php

namespace Database\Factories;

use App\Models\LanguageUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'full_name'             => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email'                 => $this->faker->unique()->safeEmail(),
            'password'              => Hash::make($this->faker->password()),
            'type'                  => $this->faker->randomElement(['customer', 'listener', 'both']),
            'bio'                   => $this->faker->sentence(),
            'date_of_birth'         => $this->faker->dateTimeBetween('1950-01-01')->format('d.m.Y'),
            'gender'                => $this->faker->randomElement(['male', 'female']),
            'phone_number'          => $this->faker->phoneNumber(),
            'balance'               => $this->faker->numberBetween(100000, 999999) * 0.01,
            'profile_url'           => $this->faker->url(),
            'language'              => $this->faker->languageCode(),
            'verification_code'     => array_rand([$this->faker->numberBetween(100000, 999999), null]),
            'device_token_customer' => Str::random(),
            'device_token_listener' => Str::random(),
        ];
    }
}
