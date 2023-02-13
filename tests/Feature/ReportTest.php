<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportTest extends TestCase
{

    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function bad_behavior_can_be_reported()
    {


        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);

        $user = User::find($conversation->caller_id);

        $response = $this->actingAs($user)
            ->post(route('report.store'), [
                'sender' => "caller",
                'comment' => $this->faker->realText(3000),
                'conversation_id' => $conversation->id
            ]);


        //dd($response);
        $response
            ->assertJson([
                'message' => 'Report successfully stored.'
            ]);
    }

    /** @test */
    public function bad_behavior_report_fails_validation()
    {

        $conversation = Conversation::factory()->create([
            'caller_id'   => $caller = User::factory()->create(['type' => 'both']),
            'listener_id' => $listener = User::factory()->create(['type' => 'both'])
        ]);

        $user = User::find($conversation->caller_id);

        $response = $this->actingAs($user)
            ->postJson(route('report.store'), [
                'sender' => 1,


            ]);

        $response->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "sender" => [
                        "Sender can be either caller or listener."
                    ],
                    "comment" => [
                        "The comment field is required."
                    ],
                    "conversation_id" => [
                        "The conversation id field is required."
                    ],
                ]
            ]);
    }
}
