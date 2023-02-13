<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateApiTestUsersTest extends TestCase
{
    use RefreshDatabase;

    public string $command = "test-users:create";

    /** @test */
    public function it_creates_users_in_database()
    {
        $this->artisan($this->command)
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
    }
}
