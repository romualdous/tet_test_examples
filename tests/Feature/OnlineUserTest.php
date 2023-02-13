<?php

namespace Tests\Feature;

use App\Models\LanguageUser;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnlineUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private Authenticatable $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    /** @test */
    public function list_of_online_users_can_be_seen()
    {
        $users = User::factory()->count($onlineUserCount = 11)->create(['type' => User::TYPE_LISTENER]);
        $users->each(function (User $user) {
            $user->update(['status' => User::STATUS_ONLINE]);
        });

        $response = $this->getJson(route('online.listeners'));


        $response->assertJsonCount(11, 'data');
    }

    /** @test */
    public function list_of_online_users_can_be_filtered_by_topic()
    {

        Topic::factory()->count(50)->create();

        $users = User::factory()->count(11)->create(['type' => User::TYPE_LISTENER, 'status' => User::STATUS_ONLINE]);

        $usersNotValid = User::factory()->count(11)->create(['type' => User::TYPE_CUSTOMER, 'status' => User::STATUS_OFFLINE]);

        $users->each(function (User $user) {
            $user->topics()->detach();
        });

        $user = User::find(2);

        $user->topics()->attach([1, 2, 3]);

        $response = $this->getJson(route('online.listeners', ['topic' => 1]));

        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function list_of_online_users_can_be_filtered_by_topic_and_languages()
    {

        Topic::factory()->count(50)->create();

        $users = User::factory()->count(11)->create(['type' => User::TYPE_LISTENER, 'status' => User::STATUS_ONLINE]);

        $users->each(function (User $user) {
            $user->topics()->detach();
        });

        $user = User::find(2);

        LanguageUser::factory()->create([
            'user_id' => $user->id,
            'language' => 'lv',

        ]);

        LanguageUser::factory()->create([
            'user_id' => $user->id,
            'language' => 'en',

        ]);

        $user->topics()->attach([1, 2, 3]);

        $response = $this->postJson(route('online.topicLanguages', ['topic_id' => 2, 'languages' => ['ru', 'lv']]));

        $response->assertJsonCount(1, 'data');
    }


    /** @test */
    public function user_can_be_updated_to_appear_online()
    {
        $this->assertFalse($this->user->isOnline());

        $this->postJson(route('online.broadcast'))
            ->assertSuccessful();

        $this->assertTrue($this->user->isOnline());
    }
}
