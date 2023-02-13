<?php

namespace Tests\Feature;

use App\Events\RatingCreated;
use App\Exceptions\ConversationException;
use App\Models\Conversation;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingControllerTest extends TestCase
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
    public function ratings_can_be_created()
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);
        $conversation->markAsFinished();

        $this->expectsEvents(RatingCreated::class);

        // As caller
        $this->actingAs($caller)->postJson(route('rating.store', ['user' => $listener]), [
            'rating'           => 4,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true,
            'comment'          => $comment = 'Great dude, would talk again'
        ])
            ->assertSuccessful()
            ->assertJson([
                'message' => 'Rating created successfully'
            ]);

        $this->assertDatabaseCount('ratings', 1);
        $this->assertEquals($comment, Rating::first()->comment);
        $this->assertCount(0, $caller->receivedRatings);
        $this->assertCount(1, $caller->givenRatings);


        // As listener
        $this->actingAs($listener)->postJson(route('rating.store', ['user' => $caller]), [
            'rating'           => 3,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true
        ])
            ->assertSuccessful()
            ->assertJson([
                'message' => 'Rating created successfully'
            ]);

        $this->assertDatabaseCount('ratings', 2);
        $this->assertCount(1, $listener->receivedRatings);
        $this->assertCount(1, $listener->givenRatings);

        // Caller should have received rating
        $this->assertCount(1, $caller->fresh()->receivedRatings);
    }

    /** @test */
    public function cannot_rate_unfinished_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);
        $conversation->markAsOngoing();

        $this->doesntExpectEvents(RatingCreated::class);

        $this->withoutExceptionHandling();
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::callNotFinished()->getMessage());

        $this->actingAs($caller)->postJson(route('rating.store', ['user' => $listener]), [
            'rating'           => 3,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true
        ]);
    }

    /** @test */
    public function rating_cannot_be_created_by_user_that_was_not_in_the_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);
        $conversation->markAsFinished();

        $this->doesntExpectEvents(RatingCreated::class);

        $this->withoutExceptionHandling();
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::userNotParticipant()->getMessage());

        $differentUser = User::factory()->create(['type' => 'both']);

        $this->actingAs($differentUser)->postJson(route('rating.store', ['user' => $listener]), [
            'rating'           => 3,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true
        ]);
    }

    /** @test */
    public function rating_cannot_be_created_for_user_that_was_not_in_the_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);
        $conversation->markAsFinished();

        $this->doesntExpectEvents(RatingCreated::class);

        $this->withoutExceptionHandling();
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::userNotParticipant()->getMessage());

        $differentListener = User::factory()->create(['type' => 'both']);

        $this->actingAs($caller)->postJson(route('rating.store', ['user' => $differentListener]), [
            'rating'           => 3,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true
        ]);
    }

    /** @test */
    public function cannot_create_a_rating_for_user_if_that_user_has_already_been_rated_in_conversation()
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);
        $conversation->markAsFinished();

        $this->withoutExceptionHandling();
        $this->expectException(ConversationException::class);
        $this->expectExceptionMessage(ConversationException::userHaveAlreadyBeenRated($listener)->getMessage());

        $conversation->ratings()->create([
            'reviewer_id'      => $caller->id,
            'recipient_id'     => $listener->id,
            'feels_better'     => true,
            'would_talk_again' => true,
            'rating'           => 4
        ]);

        $this->actingAs($caller)->postJson(route('rating.store', ['user' => $listener]), [
            'rating'           => 3,
            'conversation_id'  => $conversation->id,
            'feels_better'     => true,
            'would_talk_again' => true
        ]);
    }
}
