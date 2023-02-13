<?php

namespace Tests\Feature;

use App\Events\RatingCreated;
use App\Exceptions\UserException;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private Authenticatable $user;

    /**
     * Set up basic user.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = Sanctum::actingAs(User::factory()->create([
            'full_name' => 'Davis',
            'email'     => 'davis@ccstudio.com',
            'type'      => 'customer',
            'gender'    => 'male'
        ]));
    }

    /** @test */
    public function user_profile_data_can_be_updated()
    {
        $this->assertTrue(auth()->check());

        $image = UploadedFile::fake()->image('profile_pic.jpg');

        $attributes = [
            'full_name'     => 'Davis updated',
            'email'         => 'davisnaglis@ccstudio.com',
            'status'        => 'online',
            'gender'        => 'female',
            'language'      => 'en',
            'date_of_birth' => $dob = '12.04.1995',
            'profile_url'   => 'https://www.linkedin.com/in/vojislav-pavasovi%C4%87-80b528a1/',
            'profile_image' => $image,

        ];

        $response = $this->postJson('api/user', $attributes);

        unset($attributes['profile_image']);

        $response->assertJson([
            'data'    => $this->user->only(array_keys($attributes)),
            'message' => 'User successfully updated'
        ]);



        Storage::assertExists(config('filesystems.profile_picture_path') . $image->hashName());
        Storage::delete(config('filesystems.profile_picture_path') . $image->hashName());

        $this->assertNotSame($this->user->created_at, $this->user->updated_at);
        $this->assertEquals($dob, Carbon::parse($this->user->date_of_birth)->format('d.m.Y'));
    }

    /** @test */
    public function user_profile_data_fails_validation()
    {

        $existingUser = User::factory()->create();

        $this->assertTrue(auth()->check());

        $response = $this
            ->post('api/user', $attributes = [
                'email'         => Auth::user()->email,
                'status'        => 'none',
                'gender'        => 'lady',
                'bio'           => 'No',
                'profile_url'   => $existingUser->profile_url,
                'language'      => 'hr',
                'date_of_birth' => $dob = '12/04/1995',
            ]);

        $response->assertJson([
            "message" => "The given data was invalid.",
            "errors" => [
                "date_of_birth" => [
                    "The date of birth does not match the format d.m.Y."
                ],
                "gender" => [
                    "User can be either male or female."
                ],
                "status" => [
                    "The selected status is invalid."
                ],
                "bio" => [
                    "The bio must be at least 3 characters."
                ],
                "profile_url" => [
                    "Profile URL already taken."
                ]
            ]
        ]);
    }


    /** @test */
    public function data_about_any_user_can_be_returned()
    {
        $ids = User::factory()->count(5)->create()->pluck('id');

        $ids->each(function (int $id) {
            $this->getJson(route('index.user', ['user' => $id]))
                ->assertSuccessful();
        });
    }

    /** @test */
    public function user_profile_can_be_deleted()
    {
        $this->deleteJson('api/user')->assertSuccessful();

        $this->assertEquals(0, User::count());
        $this->assertDatabasecount('personal_access_tokens', 0);
    }

    /** @test */
    public function updating_user_to_be_something_other_than_customer_or_listener_throws_exception()
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            UserException::givenTypeInvalid(User::$availableTypes)->getMessage()
        );

        $this->user->update([
            'type' => 'just_user'
        ]);
    }

    /** @test */
    public function when_filtered_with_only_admin_scope_only_admins_get_returned()
    {
        $this->assertEquals(0, User::onlyAdmins()->count());

        // Add 1st admin
        $admin1 = User::factory()->create();
        $admin1->createToken('random', ['admin-access']);

        $this->assertEquals(1, User::onlyAdmins()->count());

        // Add 1st user
        $user1 = User::factory()->create();
        $user1->createToken('normal-token');

        $this->assertEquals(1, User::onlyAdmins()->count());

        // Add 2nd user
        $user2 = User::factory()->create();
        $user2->createToken('normal-token');
        $this->assertEquals(1, User::onlyAdmins()->count());

        $admin2 = User::factory()->create();
        $admin2->createToken('random', ['admin-access']);

        $this->assertEquals(2, User::onlyAdmins()->count());
        $this->assertEquals(2, User::onlyNormal()->count());
    }

    /** @test */
    public function average_rating_can_be_calculated()
    {
        Rating::create([
            'conversation_id'  => $c1 = Conversation::factory()->create([
                'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
                'listener_id' => $listener = User::factory()->create(['type' => 'both'])
            ])->id,
            'reviewer_id'      => $caller->id,
            'recipient_id'     => $listener->id,
            'feels_better'     => true,
            'would_talk_again' => true,
            'rating'           => 4
        ]);

        // For now, manually dispatching event since
        // it only happens in controller after rating gets created.
        RatingCreated::dispatch($listener);

        Rating::create([
            'conversation_id'  => $c2 = Conversation::factory()->create([
                'caller_id'   => $caller,
                'listener_id' => $listener
            ])->id,
            'reviewer_id'      => $caller->id,
            'recipient_id'     => $listener->id,
            'feels_better'     => true,
            'would_talk_again' => true,
            'rating'           => 5
        ]);

        RatingCreated::dispatch($listener);

        $listener = $listener->fresh('receivedRatings');

        $this->assertCount(2, $listener->receivedRatings);
        $this->assertEquals(4.5, $listener->rating);
    }

    /** @test */
    public function user_gets_assigned_all_topics_when_created()
    {

        Topic::factory()->count(50)->create();

        $user = User::factory()->create();

        $user->refresh();

        $this->assertEquals($user->topics->count(), 50);
    }

    /** @test */
    public function users_transactions_can_be_listed()
    {

        $user = User::factory()->create([
            'full_name' => 'Vojislav Pavasović',
            'email'     => 'vpavasov@gmail.com',
            'type'      => 'customer',
            'gender'    => 'male',

        ]);

        Transaction::factory()->count(30)->create([
            'payment_id' => Payment::factory()->create([
                "payer_id" => $user->id
            ])
        ]);
        Conversation::factory()->count(20)->create([
            "caller_id" => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('transactions.list'));

        $response->assertSuccessful();
        $response->assertJsonCount(50, 'data');
    }
}
