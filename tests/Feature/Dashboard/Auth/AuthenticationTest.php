<?php

namespace Tests\Feature\Dashboard\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User
     */
    private User $user;

    /**
     * @var string
     */
    private string $password;

    /**
     * Setup admin user.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password = 'password')
        ]);
        $this->user->createToken('admin', ['admin-access']);
    }

    /** @test */
    public function existing_admin_can_log_in_through_form()
    {
        $response = $this->postJson(route('admin.login'), [
            'email' => $this->user->email,
            'password' => $this->password
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully authenticated!'
            ]);

        $this->assertTrue(auth()->guard('admin')->check());
        $this->assertInstanceOf(User::class, auth()->guard('admin')->user());
        $this->assertCount(1, $this->user->fresh()->logins);
    }

    /** @test */
    public function normal_user_cannot_log_in_as_admin()
    {
        $normalUser = User::factory()->create(['password' => Hash::make($this->password)]);

        $response = $this->postJson(route('admin.login'), [
            'email' => $normalUser->email,
            'password' => $this->password
        ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'There has been an error while trying to authenticated. Try again.'
            ]);
    }

    /** @test */
    public function normal_user_cannot_reset_their_password()
    {
        Notification::fake();
        $normalUser = User::factory()->create();

        $this->postJson(route('admin.forgot-password'), [
            'email' => $normalUser->email
        ])->assertNotFound()
            ->assertJson([
                'message' => "User who's password you are trying to request to reset, is not an admin"
            ]);

        Notification::assertNotSentTo($normalUser, ResetPassword::class);
    }
}
