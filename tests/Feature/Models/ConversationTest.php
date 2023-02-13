<?php

namespace Tests\Feature\Models;

use App\Exceptions\ConversationException;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User|null
     */
    public ?User $listener = null;

    /**
     * @var User|null
     */
    public ?User $customer = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['type' => 'customer']);
        $this->listener = User::factory()->create(['type' => 'listener']);
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

    /** @test */
    public function starting_conversation_should_return_instance_of_itself()
    {
        $this->switchOnlineStatusesTo('online');

        $conversation = Conversation::start($this->customer, $this->listener);

        $this->assertDatabaseHas('conversations', $conversation->only(['id', 'status', 'caller_id', 'listener_id']));
    }

    /** @test */
    public function conversation_has_valid_participants()
    {
        /**
         * It would be nice for us to use data providers, but PHPUnit loads data providers
         * before the framework itself is launched and even calling $this->createApplication()
         * won't be able to solve the issue since they need to count the amount of tests before.
         * Issue is within PHPUnit itself, rather than framework. Issue for 7 years and counting.
         *
         * @see https://github.com/laravel/framework/issues/27599#issuecomment-465757477
         */

        // Customer is supposed to call listener
        $hasValidParticipants = (new Conversation)->hasValidParticipants($this->customer, $this->listener);
        $this->assertEquals(true, $hasValidParticipants);

        // 2 customers shouldn't be able to call each other
        $hasValidParticipants = (new Conversation)->hasValidParticipants($this->user('customer'), $this->user('customer'));
        $this->assertEquals(false, $hasValidParticipants);

        // Listener shouldn't be the one calling
        $hasValidParticipants = (new Conversation)->hasValidParticipants($this->user('listener'), $this->user('customer'));
        $this->assertEquals(false, $hasValidParticipants);

        // Since both users can be both customers, they can call each other
        $hasValidParticipants = (new Conversation)->hasValidParticipants($this->user('both'), $this->user('both'));
        $this->assertEquals(true, $hasValidParticipants);

        // 2 listeners shouldn't be able to call each other
        $hasValidParticipants = (new Conversation)->hasValidParticipants($this->user('listener'), $this->user('listener'));
        $this->assertEquals(false, $hasValidParticipants);
    }

    /** @test */
    public function conversation_can_show_its_participants_correctly()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $participants = $conversation->participants();
        $this->assertCount(2, $participants);

        $conversation->participants()->each(function (User $user) {
            $this->assertInstanceOf(User::class, $user);
        });
    }

    /** @test */
    public function conversation_can_correctly_check_its_status()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->assertEquals(Conversation::STATUS_REQUESTED, $conversation->status);
        $this->assertFalse($conversation->isOngoing());
    }

    /** @test */
    public function conversation_provides_channel_name_and_token()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->assertNotnull($conversation->token());
        $this->assertNotNull($conversation->channel());
    }

    /**
     * @test
     * @dataProvider invalidConversationStatuses
     *
     * @param string $status
     * @throws ConversationException
     */
    public function status_cannot_be_set_to_any_gibberish(string $status)
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(
            ConversationException::invalidStatusProvided(Conversation::$availableStatuses)->getMessage()
        );

        $conversation->update([
            'status' => $status
        ]);
    }

    /**
     * @test
     * @dataProvider validConversationStatuses
     *
     * @param string $status
     * @throws ConversationException
     */
    public function status_can_be_only_set_to_valid_statuses(string $status)
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $update = $conversation->update([
            'status' => $status
        ]);

        $this->assertTrue($update);
    }

    /** @test */
    public function it_can_be_marked_as_ongoing()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->assertEquals(Conversation::STATUS_REQUESTED, $conversation->status);
        $conversation->markAsOngoing();
        $this->assertEquals(Conversation::STATUS_ONGOING, $conversation->status);
    }

    /** @test */
    public function it_can_be_marked_as_finished()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->assertEquals(Conversation::STATUS_REQUESTED, $conversation->status);

        /**
         * Without this, there's no "started_at" timestamp.
         * Only cancelled calls should have no "started_at" timestamp.
         */
        $conversation->markAsOngoing();

        $conversation->markAsFinished();

        $this->assertEquals(Conversation::STATUS_FINISHED, $conversation->status);
    }

    /**
     * @test
     * @dataProvider validDurations
     * @param int $providedDuration
     * @param int $storedDuration
     * @throws ConversationException
     */
    public function after_call_is_ended_duration_for_it_gets_set_properly(int $providedDuration, int $storedDuration)
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $conversation->markAsOngoing();
        $this->travel($providedDuration)->seconds();
        $conversation->markAsFinished();

        $this->assertEquals($storedDuration, $conversation->duration);
    }

    /**
     * @test
     * @dataProvider validDurations
     * @param int $providedDuration
     * @param int $storedDuration
     * @throws ConversationException
     */
    public function call_duration_added_to_listener_and_subtracted_from_caller(int $providedDuration, int $storedDuration)
    {

        $this->switchOnlineStatusesTo('online');

        $start = $this->actingAs($this->customer)
            ->post('/api/call', ['listener_id' => $this->listener->id]);


        $accept = $this->actingAs($this->listener)
            ->post('/api/call/accept', ['conversation_id' => $start['data']['conversation_id']]);

        $this->travel($providedDuration)->seconds();

        $finish = $this->actingAs($this->customer)
            ->post('/api/call/finish', ['conversation_id' => $start['data']['conversation_id']]);


        $listener = User::find($this->listener->id);
        $caller = User::find($this->customer->id);

        $this->assertEquals($this->customer->balance - $providedDuration, $caller->balance);
        $this->assertEquals($this->listener->balance + $providedDuration, $listener->balance);
    }

    /**
     * @test
     * @dataProvider invalidDurations
     * @param int $providedDuration
     * @throws ConversationException
     */
    public function invalid_durations_will_not_be_set_and_will_throw_exception(int $providedDuration)
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(
            ConversationException::timestampsHaveBeenManipulated()->getMessage()
        );

        $conversation->update([
            // "finished_at" gets set "internally", so I'll just set "started_at" in future
            'started_at' => now()->addSeconds(abs($providedDuration))
        ]);

        $conversation->markAsFinished();

        // In these cases, update query isn't succeeding
        $this->assertEquals(0, $conversation->duration);
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $this->switchOnlineStatusesTo('online');
        $conversation = Conversation::start($this->customer, $this->listener);

        $conversation->markAsCancelled();

        $this->assertEquals(Conversation::STATUS_CANCELLED, $conversation->status);
        $this->assertNull($conversation->finished_at);
    }

    /** @test */
    public function it_correctly_indicates_if_two_users_are_having_a_phone_call()
    {
        $this->switchOnlineStatusesTo('online');
        Conversation::start($this->customer, $this->listener);

        $this->assertTrue(Conversation::hasActiveCallBetween(customer: $this->customer, listener: $this->listener));
        $randomUser = User::factory()->create(['type' => 'listener']);
        $this->assertFalse(Conversation::hasActiveCallBetween(customer: $this->customer, listener: $randomUser));
    }

    /** @test */
    public function customer_cannot_call_listener_who_is_not_available_for_call()
    {
        $this->customer->update(['status' => 'online']);
        $this->listener->update(['status' => 'on-call']);

        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::notAvailableForCall($this->listener)->getMessage());

        Conversation::start($this->customer->fresh(), $this->listener->fresh());
    }

    /**
     * Possible durations in a normal flow.
     *
     * @return \int[][]
     */
    public function validDurations(): array
    {
        return [
            [1, 1],
            [60, 60],
            [3245, 3245],
        ];
    }

    /**
     * Invalid durations which may arise on
     * server-side time manipulations.
     *
     * @return \int[][]
     */
    public function invalidDurations(): array
    {
        return [
            [-1],
            [-2345],
        ];
    }

    /**
     * Provide invalid conversation statuses.
     *
     * @return \string[][]
     */
    public function invalidConversationStatuses(): array
    {
        return [
            ['pending'],
            ['starting'],
            ['test'],
            ['blah blah']
        ];
    }

    /**
     * Provide valid conversation statuses.
     *
     * @return \string[][]
     */
    public function validConversationStatuses(): array
    {
        return [
            ['requested'],
            ['on-going'],
            ['cancelled'],
            ['finished'],
        ];
    }

    /**
     * Whip up user with specific user type.
     *
     * @param string $type
     * @param array|null $fields
     * @return User|null
     */
    private function user(string $type, array $fields = null): ?User
    {
        if (!in_array($type, User::$availableTypes)) {
            return null;
        }

        return User::factory()->create(
            array_merge($fields ?? [], ['type' => $type])
        );
    }
}
