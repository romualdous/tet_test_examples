<?php

namespace Tests\Feature;

use App\Events\Calls\CallFinished;
use App\Exceptions\ConversationException;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Authenticatable
     */
    private Authenticatable $customer;

    /**
     * @var Authenticatable
     */
    private Authenticatable $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['type' => 'customer']);
        $this->listener = User::factory()->create(['type' => 'listener']);

        $this->logInWith($this->customer);
        $this->switchOnlineStatusesTo('online');
    }

    /**
     * @param string $status
     * @return void
     */
    private function switchOnlineStatusesTo(string $status): void
    {
        $this->customer->update(['status' => $status]);
        $this->listener->update(['status' => $status]);
    }

    /**
     * @param Authenticatable $user
     */
    private function logInWith(Authenticatable $user)
    {
        Sanctum::actingAs($user);
    }

    /** @test */
    public function customer_can_initiate_call_with_customer()
    {
        $this->withoutExceptionHandling();
        $this->switchOnlineStatusesTo('online');

        $this->postJson(route('call.start'), [
            'listener_id' => $this->listener->id
        ])->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Call has been initiated',
            ])->assertJsonCount(3, 'data');

        $this->assertDatabaseCount('conversations', 1);
        $this->assertEquals($this->listener->id, Conversation::first()->listener_id);
    }

    /** @test */
    public function conversation_cannot_be_started_if_listener_is_offline()
    {
        $this->withoutExceptionHandling();
        $this->switchOnlineStatusesTo('offline');

        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::notAvailableForCall($this->listener)->getMessage());

        $this->postJson(route('call.start'), [
            'listener_id' => $this->listener->id
        ]);
    }

    /** @test */
    public function listener_cannot_make_a_call_to_other_users()
    {
        $this->withoutExceptionHandling();
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::invalidParticipants()->getMessage());

        $this->postJson(route('call.start'), [
            'listener_id' => $this->customer->id
        ]);

        $this->assertDatabaseCount('conversations', 0);
    }

    /** @test */
    public function event_gets_fired_about_ended_call()
    {
        Event::fake(CallFinished::class);
        $this->expectsEvents(CallFinished::class);

        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);
        $conversation->markAsOngoing();

        $this->postJson(route('call.finish'), [
            'conversation_id' => $conversation->id
        ])->assertSuccessful()
            ->assertJson([
                'message' => 'Call successfully finished'
            ]);

        $this->assertEquals(Conversation::STATUS_FINISHED, $conversation->fresh()->status);
    }

    /** @test */
    public function listener_can_accept_call()
    {
        $this->withoutExceptionHandling();
        $this->logInWith($this->listener);

        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);

        $this->postJson(route('call.accept'), [
            'conversation_id' => $conversation->id
        ])->assertSuccessful()
            ->assertJson([
                'message' => 'Call has been accepted'
            ]);
    }

    /** @test */
    public function already_ongoing_conversation_cannot_be_accepted_again()
    {
        $this->withoutExceptionHandling();

        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);
        $conversation->markAsOngoing();

        $this->expectException(AuthorizationException::class);

        $this->postJson(route('call.accept'), [
            'conversation_id' => $conversation->id
        ]);
    }

    /** @test */
    public function call_which_is_not_ongoing_right_now_cannot_be_finished()
    {
        $this->withoutExceptionHandling();

        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);

        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::callNotActive()->getMessage());

        $this->postJson(route('call.finish'), [
            'conversation_id' => $conversation->id
        ]);
    }

    /** @test */
    public function call_can_be_cancelled_by_any_of_both_participants()
    {
        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);

        $this->postJson(route('call.cancel'), [
            'conversation_id' => $conversation->id
        ])->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Call has been cancelled',
                'data'    => []
            ]);

        $conversation = $conversation->fresh();

        $this->assertDatabaseHas('conversations', $conversation->only('id'));
        $this->assertNull($conversation->finished_at);
        $this->assertEquals(Conversation::STATUS_CANCELLED, $conversation->status);
    }

    /** @test */
    public function conversation_cannot_be_cancelled_by_a_random_user()
    {
        $this->withoutExceptionHandling();
        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);
        $this->expectException(AuthorizationException::class);

        // Let's log in as random user that's not even in call
        $this->logInWith(User::factory()->create());

        $this->postJson(route('call.cancel'), [
            'conversation_id' => $conversation->id
        ])->assertForbidden();
    }

    /** @test */
    public function user_cannot_call_himself()
    {
        $this->logInWith($me = User::factory()->create(['type' => 'both']));
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::cannotCallYourself()->getMessage());

        /** @var Conversation $conversation */
        $me->startConversationWith($me);
    }

    /** @test */
    public function agora_token_during_the_conversation_can_be_regenerated_by_both_participants()
    {
        $this->withoutExceptionHandling();
        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);
        $conversation->markAsOngoing();

        $this->postJson(route('channel.refresh-token'), [
            'channel' => $conversation->channel
        ])->assertSuccessful();
    }

    /** @test */
    public function agora_token_cannot_be_regenerated_by_completely_different_user_out_of_conversation()
    {
        $this->withoutExceptionHandling();

        /** @var Conversation $conversation */
        $conversation = $this->customer->startConversationWith($this->listener);
        $conversation->markAsOngoing();

        $this->expectException(AuthorizationException::class);

        $this->actingAs(User::factory()->create())->postJson(route('channel.refresh-token'), [
            'channel' => $conversation->channel
        ])->assertForbidden();
    }

    /** @test */
    public function list_of_calls_can_be_returned()
    {

        $users = User::factory()->count(80)->create();

        $conversations = Conversation::factory()->count(100)->create([
            'caller_id' => $this->customer->id,
            'listener_id' => rand(25, 50),
        ]);
        $conversations = Conversation::factory()->count(50)->create([
            'caller_id' => rand(2, 24),
            'listener_id' => $this->customer->id,
        ]);
        $conversations = Conversation::factory()->count(6)->create([
            'caller_id' => 60,
            'listener_id' => $this->customer->id,
        ]);
        $conversations = Conversation::factory()->count(7)->create([
            'caller_id' => $this->customer->id,
            'listener_id' => 70,
        ]);


        $response = $this->postJson(route('call.list'), []);



        $response->assertJsonCount(163, 'data');

        $response = $this->postJson(route('call.list'), ['caller' => 60, 'listener' => 70]);
        $response->assertJson([
            'success' => false,
            'message' => 'Both parameters are not allowed.'
        ]);

        $response = $this->postJson(route('call.list'), ['listener' => 70]);
        $response->assertJsonCount(7, 'data');

        $response = $this->postJson(route('call.list'), ['caller' => 60]);

        $response->assertJsonCount(6, 'data');

        $response = $this->postJson(route('call.list'), ['listener' => $this->customer->id]);
        $response->assertJsonCount(56, 'data');

        $response = $this->postJson(route('call.list'), ['caller' => $this->customer->id]);
        $response->assertJsonCount(107, 'data');
    }
}
