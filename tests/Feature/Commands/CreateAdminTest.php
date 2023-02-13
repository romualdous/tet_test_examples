<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var string
     */
    private string $command = 'admin:create';

    /**
     * Setting lower min password length limit.
     */
    public function setUp(): void
    {
        parent::setUp();

        config(['administrator.settings.passwords.min_length' => 5]);
    }

    /** @test */
    public function it_creates_an_administrator_successfully_that_can_log_in_as_admin_successfully()
    {
        $this->artisan($this->command)
            ->expectsQuestion('Provide an e-mail address for new administrator account', $email = 'davis@ccstudio.com')
            ->expectsQuestion('Provide a password for new administrator account', $password = 'password')
            ->expectsOutput('Administrator successfully created!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        $this->assertTrue(Hash::check($password, User::first()->password));

        // Since auth succeeds, admin token exists for user.
        // Token existence will be checked with 'admin' guard tests.
        auth()->guard('admin')->attempt(['email' => $email, 'password' => $password]);

        $this->assertInstanceOf(User::class, auth()->guard('admin')->user());
        $this->assertEquals(
            User::first()->only(['email', 'password']),
            auth()->guard('admin')->user()->only(['email', 'password'])
        );
    }

    /** @test */
    public function existing_user_cannot_be_created_as_admin_with_this_command()
    {
        User::factory()->create(['email' => $email = 'davis@ccstudio.com']);

        $this->artisan($this->command)
            ->expectsQuestion('Provide an e-mail address for new administrator account', $email)
            ->expectsQuestion('Provide a password for new administrator account', 'password')
            ->doesntExpectOutput('Administrator successfully created!')
            ->expectsOutput('User with provided e-mail address already exists')
            ->assertExitCode(-1);
    }
}
