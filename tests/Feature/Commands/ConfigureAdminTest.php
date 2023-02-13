<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfigureAdminTest extends TestCase
{
    use RefreshDatabase;

    private string $command = 'admin:configure';

    /** @test */
    public function user_with_no_email_and_password_can_be_set_as_an_admin()
    {
        $user = User::factory()->create([
            'email' => null,
            'password' => null
        ]);

        $this->artisan($this->command, [
                'user' => $user->id
            ])
            ->expectsConfirmation('User has no email address. Without it user cannot be admin. Should I set it up?', 'yes')
            ->expectsQuestion('Please, enter e-mail address for user', $email = 'davis@ccstudio.com')
            ->expectsConfirmation('User has no password. Without it user cannot be admin. Should I set it up?', 'yes')
            ->expectsQuestion('Please, enter password for user', 'password')
            ->expectsOutput('User had no old admin access tokens to delete. Nothing got deleted.')
            ->expectsOutput("User (ID: 1, e-mail: {$email}) successfully configured as admin!")
            ->assertExitCode(0);

        $user = $user->fresh();

        $this->assertEquals($email, $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /** @test */
    public function user_with_credentials_set_and_needed_token_with_abilities_is_already_a_legit_admin()
    {
        $legitAdmin = User::factory()->create([
           'email' => 'davis@ccstudio.com',
           'password' => \Hash::make('password')
        ]);

        $legitAdmin->createToken('test', ['admin-access']);

        $this->artisan($this->command, [
            'user' => $legitAdmin->id
        ])
            ->expectsOutput("User '{$legitAdmin->email}' is already an administrator. All done!")
            ->assertExitCode(0);
    }

    /** @test */
    public function user_without_set_email_will_be_asked_to_set_email_through_terminal()
    {
        $user = User::factory()->create([
            'email' => null,
            'password' => \Hash::make('password')
        ]);

        $this->artisan($this->command, [
            'user' => $user->id
        ])
            ->expectsConfirmation('User has no email address. Without it user cannot be admin. Should I set it up?', 'yes')
            ->expectsQuestion('Please, enter e-mail address for user', 'davis@ccstudio.com')
            ->assertExitCode(0);
    }

    /** @test */
    public function declining_to_set_email_for_user_without_email_address_will_exit_the_script()
    {
        $user = User::factory()->create([
            'email' => null,
            'password' => \Hash::make('password')
        ]);

        $this->artisan($this->command, [
            'user' => $user->id
        ])
            ->expectsConfirmation('User has no email address. Without it user cannot be admin. Should I set it up?', 'no')
            ->expectsOutput('User email has not been set. Exiting.')
            ->assertExitCode(0);
    }

    /** @test */
    public function user_that_does_not_exist_exits_script_with_message()
    {
        $this->assertEquals(0, User::count());

        $this->artisan($this->command, [
            'user' => 'davis@csstudio.com'
        ])
            ->expectsOutput('User with given data not found. Perhaps, user has no email, but will always have ID')
            ->assertExitCode(-1);
    }
}
