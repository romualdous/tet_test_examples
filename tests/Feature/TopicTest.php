<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;


    /** @test */
    public function topics_from_request_get_assigned_to_user()
    {
        Topic::factory()->count(50)->create();

        $user = User::factory()->create();

        $user->refresh();

        $arrayOfTopicIds = [3, 5, 7, 1, 8, 6];

        $response = $this->actingAs($user)->postJson(route('topic.attachToUser'), ['topics' => $arrayOfTopicIds]);

        $user->refresh();

        $topics = Topic::whereIn('id', $arrayOfTopicIds)->get()->sort()->toArray();

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => array_values($topics)
            ]);
    }
}
