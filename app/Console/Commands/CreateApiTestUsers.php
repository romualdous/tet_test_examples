<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateApiTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-users:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test user just to test it with Postman';

    /**
     * @var array|array[]
     */
    public array $usersToSeed = [
        [
            'email'        => 'customer@test.com',
            'phone_number' => '+37122222222',
            'type'         => User::TYPE_CUSTOMER
        ],
        [
            'email'        => 'listener@test.com',
            'phone_number' => '+37133333333',
            'type'         => User::TYPE_LISTENER
        ]
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Only allowed to run in development environment!');

            return -1;
        }

        $this->cleanOldTestUserData(
            collect($this->usersToSeed)->pluck('email')->toArray()
        );

        $users = collect($this->usersToSeed)
            ->transform(function (array $userData) {
                /** @var User $user */
                $user = User::factory()->create($userData);
                $userData['token'] = $user->createToken(Str::random())->plainTextToken;

                return $userData;
            });

        $this->table(
            ['E-Mail', 'Phone Number', 'Type', 'API Access Token'],
            $users
        );

        $this->info('All done!');

        return 0;
    }

    private function cleanOldTestUserData(array $emails): void
    {
        $query = User::whereIn('email', $emails);

        $query->get()
            ->each(fn (User $user) => $user->tokens()->delete());

        $query->forceDelete();
    }
}
