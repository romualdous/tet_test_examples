<?php

namespace Tests\Feature\Dashboard;

use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private $user;

    /**
     * Credentials used for test admin.
     *
     * @var string[]
     */
    private array $credentials = [
        'email'    => 'davis@ccstudio.com',
        'password' => 'password'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            $this->user = User::factory()->create([
                'email'    => $this->credentials['email'],
                'password' => Hash::make($this->credentials['password'])
            ]),
            'admin'
        );
    }

    /** @test */
    public function list_of_all_users_can_be_displayed()
    {
        [$user1, $user2] = User::factory()->count(2)->create();

        $this->getJson(route('admin.users.index'))
            ->assertSuccessful()
            ->assertJson([
                'data' => [
                    $this->user->toArray(),
                    $user1->toArray(),
                    $user2->toArray()
                ]
            ]);
    }

    /** @test */
    public function admin_can_update_his_own_password_and_log_in_with_it()
    {
        $this->postJson(route('admin.profile.update-password'), [
            'current_password'          => $this->credentials['password'],
            'new_password'              => $newPassword = 'newpassword',
            'new_password_confirmation' => $newPassword
        ])->assertSuccessful()
            ->assertJson([
                'message' => 'Successfully updated password!'
            ]);

        $this->assertTrue(Hash::check($newPassword, $this->user->password));

        $this->postJson(route('admin.login', [
            'email'    => $this->user->email,
            'password' => $newPassword
        ]))->assertSuccessful();

        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function updating_password_with_wrong_current_password_throws_error()
    {
        $this->postJson(route('admin.profile.update-password'), [
            'current_password'          => 'wrong-password',
            'new_password'              => $newPassword = 'newpassword',
            'new_password_confirmation' => $newPassword
        ])->assertJsonValidationErrors([
            'current_password' => 'Password given does not match current password.'
        ]);
    }

    /** @test */
    public function matching_new_password_and_confirmation_must_be_supplied()
    {
        $this->postJson(route('admin.profile.update-password'), [
            'current_password'          => $this->credentials['password'],
            'new_password'              => 'newpassword',
            'new_password_confirmation' => 'not-the-same-password'
        ])->assertJsonValidationErrors([
            'new_password' => 'The new password confirmation does not match.'
        ]);

        $this->postJson(route('admin.profile.update-password'), [
            'current_password' => $this->credentials['password'],
            'new_password'     => 'newpassword',
        ])->assertJsonValidationErrors([
            'new_password' => 'The new password confirmation does not match.'
        ]);
    }

    /** @test */
    public function admin_can_update_his_profile_data()
    {
        $this->assertTrue($this->user->full_name !== 'Dāvis');

        $this->postJson(route('admin.profile.update-info'), [
            'full_name' => $updatedFullName = 'Dāvis updated',
        ])->assertSuccessful();

        $this->assertTrue($this->user->full_name === $updatedFullName);
    }

    /** @test */
    public function a_new_user_can_be_stored_from_dashboard()
    {
        $this->postJson(route('admin.users.store'), [
            'first_name'   => 'Davis',
            'last_name'    => 'N',
            'email'        => 'test@test.com',
            'phone_number' => '+37129179850',
            'type'         => 'customer',
            'age'          => 24,
            'gender'       => 'male'
        ]);

        $this->getJson(route('admin.users.index'))
            ->assertSuccessful()
            ->assertJson([
                'data' => [
                    $this->user->toArray(),
                    User::firstWhere('email', 'test@test.com')->toArray()
                ]
            ]);
    }

    /** @test */
    public function admin_can_view_user_info()
    {
        $this->getJson(route('admin.users.show', [
            'user' => $this->user->id
        ]))
            ->assertSuccessful()
            ->assertJson([
                'data' => $this->user->toArray()
            ]);
    }

    /** @test */
    public function admin_can_edit_user_info()
    {
        $this->putJson(route('admin.users.update', [
            'user' => $this->user
        ]), [
            'full_name' => $newFullName = 'changed the name',
        ])
            ->assertSuccessful()
            ->assertJson([
                'data' => [
                    'full_name' => $newFullName,
                ]
            ]);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        // For now there's only user.
        $this->assertDatabaseCount('users', 1);
        $user = User::factory()->create();
        $this->assertDatabaseCount('users', 2);

        $this->deleteJson(route('admin.users.destroy', [
            'user' => $user
        ]))
            ->assertSuccessful()
            ->assertJson([
                'message' => 'User successfully deleted!'
            ]);

        $this->assertEquals(1, User::count());
    }

    /** @test */
    public function admin_can_set_per_page_parameter_for_user_index()
    {
        // 1 admin + 14 random users = default per_page = 15
        User::factory()->count(14)->create();

        // Using default per_page of 15
        $this->getJson(route('admin.users.index'))
            ->assertJsonCount(15, 'data');

        // Adjusting per_page to 5
        $this->getJson(route('admin.users.index', ['per_page' => 5]))
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'meta' => [
                    'per_page'  => 5,
                    'last_page' => 3,
                    'total'     => 15
                ]
            ]);
    }

    /** @test */
    public function some_user_attributes_are_mandatory()
    {
        $this->postJson(route('admin.users.store'), [
            'first_name'   => 'Davis',
            'last_name'    => 'N',
            'phone_number' => '+37129179850',
            'age'          => 24,
        ])->assertJsonValidationErrors([
            'type', 'email'
        ]);
    }

    /** @test */
    public function request_body_which_updates_user_data_can_be_empty()
    {
        $this->postJson(route('admin.users.update', ['user' => User::factory()->create()]), [])
            ->assertJsonMissingValidationErrors(['first_name', 'last_name', 'photo']);
    }

    /** @test */
    public function image_provided_for_profile_image_cannot_exceed_5_mb()
    {
        Storage::fake();
        $file = UploadedFile::fake()->image('test.jpg')->size(7000);

        $this->putJson(route('admin.users.update', ['user' => User::factory()->create()]), [
            'photo' => $file
        ])->assertJsonValidationErrors(['photo']);
    }

    /** @test */
    public function admin_can_update_any_user_profile()
    {
        $user = User::factory()->create([
            'type' => 'customer'
        ]);
        $user->devices()->create(Device::factory()->make([
            'user_id'    => $user->id,
            'deleted_at' => null
        ])->toArray());

        // TODO DOB should be stored the same way it's retrieved to avoid confusion in test failures
        $attributes = [
            'full_name' => 'Davis Naglis',
            'bio'       => 'Test bio',
            'email'     => 'test@gmail.com',
            'gender'    => 'male',
            'type'      => 'listener'
        ];

        $this->patchJson(route('admin.users.update', $user), $attributes)->assertSuccessful();
        $this->assertDatabaseHas('users', $attributes);
    }
}
