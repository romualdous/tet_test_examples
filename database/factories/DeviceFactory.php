<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'device_id'  => Str::random(30),
            'type'       => Arr::random(['android', 'ios']),
            'user_id'    => User::factory(),
            'user_agent' => $this->faker->userAgent,
            'deleted_at' => Arr::random([null, now()])
        ];
    }
}
