<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider userTypeProvider
     * @param $currentType
     * @param $input
     * @param $output
     */
    public function correct_user_type_gets_returned($currentType, $input, $output)
    {
        User::factory()->create([
            'email'        => 'davis@ccstudio.com',
            'phone_number' => $phoneNumber = '+37129179850',
            'type'         => $currentType
        ]);

        $this->assertEquals($output, User::getCorrectType($phoneNumber, $input));
    }

    /** @test */
    public function date_of_birth_can_be_returned_in_string_format()
    {
        $this->assertTrue(is_string(User::factory()->create()->date_of_birth));
    }

    /**
     * User type casts that have to be tested.
     *
     * @return string[][]
     */
    public function userTypeProvider(): array
    {
        return [
            ['customer', 'customer', 'customer'],
            ['customer', 'listener', 'both'],
            ['customer', 'both', 'both'],

            ['listener', 'customer', 'both'],
            ['listener', 'listener', 'listener'],
            ['listener', 'both', 'both'],

            ['both', 'customer', 'both'],
            ['both', 'listener', 'both'],
            ['both', 'both', 'both'],
        ];
    }

    /** @test */
    public function correct_token_gets_generated_given_the_device_id()
    {
        $user = User::factory()->create(['id' => 48]);

        $token = $user->generateTokenName(deviceID: 90);

        $this->assertEquals('user_48_device_90', $token);
    }
}
