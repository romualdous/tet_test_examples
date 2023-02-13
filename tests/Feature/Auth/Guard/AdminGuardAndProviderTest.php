<?php

namespace Tests\Feature\Auth\Guard;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuardAndProviderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function created_admin_can_be_retrieved_through_admin_model()
    {
        $this->assertEquals(0, User::onlyAdmins()->count());

        $admin = User::factory()->create();
        $admin->createToken('some sort of token', ['admin-access']);

        $this->assertEquals(1, User::onlyAdmins()->count());
    }

    /** @test */
    public function creating_a_normal_user_will_not_make_it_appear_from_admin_queries_as_admin()
    {
        $this->assertEquals(0, User::onlyAdmins()->count());

        $admin = User::factory()->create();
        $admin->createToken('some sort of token', ['admin-access']);

        $this->assertEquals(1, User::onlyAdmins()->count());

        $user = User::factory()->create();
        $this->assertEquals(0, $user->tokens()->count());

        $this->assertEquals(1, User::onlyAdmins()->count());
    }
}
