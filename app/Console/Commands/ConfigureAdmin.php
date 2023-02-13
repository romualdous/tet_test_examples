<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ConfigureAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:configure {user : The ID or e-mail address for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user to have administrator access to dashboard';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = $this->findUser($this->argument('user'));

        if (! $user) {
            $this->error("User with given data not found. Perhaps, user has no email, but will always have ID");

            return -1;
        }

        $this->setupAdministratorAccessFor($user);

        return 0;
    }

    /**
     * @param string $argument
     * @return User|null
     */
    private function findUser(string $argument): ?User
    {
        $field = $this->isEmailProvided($argument) ? 'email' : 'id';

        if ($field === 'id') {
            $argument = (int) $argument;
        }

        return User::firstWhere([
            $field => $argument
        ]);
    }

    /**
     * @param int|string $argument
     * @return bool
     */
    private function isEmailProvided(int|string $argument): bool
    {
        return filter_var($argument, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param User $user
     * @return User|null
     */
    private function setupAdministratorAccessFor(User $user): ?User
    {
        if ($this->isCapableToAccessAdminDashboard($user)) {
            $this->info("User '{$user->email}' is already an administrator. All done!");

            return null;
        }

        if (! $user->email) {
            if (! $this->setUpEmail($user)) {
                $this->info('User email has not been set. Exiting.');

                return null;
            }
        }

        if (! $user->password) {
            if (! $this->setUpPassword($user)) {
                $this->info('User password has not been set. Exiting.');

                return null;
            }
        }

        $this->assignToken($user);

        $this->info("User (ID: {$user->id}, e-mail: {$user->email}) successfully configured as admin!");

        return $user;
    }

    /**
     * @param User $user
     * @return bool
     */
    private function isCapableToAccessAdminDashboard(User $user): bool
    {
        return $this->hasCredentialsSet($user) && $user->hasAbility('admin-access');
    }

    /**
     * @param User $user
     * @return bool
     */
    private function hasCredentialsSet(User $user): bool
    {
        return $user->email && $user->password;
    }

    /**
     * @param User $user
     */
    private function cleanOldAdminAccessTokens(User $user): void
    {
        $success = $user->tokens()->where('abilities', '["admin-access"]')->delete();

        if ($success) {
            $this->info('Old admin access tokens for user has been deleted!');

            return;
        }

        $this->info('User had no old admin access tokens to delete. Nothing got deleted.');
    }

    /**
     * @param User $user
     * @return User|null
     */
    private function setUpEmail(User $user): ?User
    {
        $shouldSetupEmail = $this->confirm('User has no email address. Without it user cannot be admin. Should I set it up?', true);

        if ($shouldSetupEmail) {
            $emailProvided = $this->ask('Please, enter e-mail address for user');

            $user->forceFill(['email' => $emailProvided])->save();

            return $user;
        }

        return null;
    }

    /**
     * @param User $user
     * @return User|null
     */
    private function setUpPassword(User $user): ?User
    {
        $shouldSetupPassword = $this->confirm('User has no password. Without it user cannot be admin. Should I set it up?', true);

        if ($shouldSetupPassword) {
            $passwordProvided = $this->ask('Please, enter password for user');

            $user->forceFill(['password' => Hash::make($passwordProvided)])->save();

            return $user;
        }

        return null;
    }

    /**
     * @param User|null $user
     */
    private function assignToken(?User $user): void
    {
        $this->cleanOldAdminAccessTokens($user);
        $user->createToken('admin', ['admin-access']);
    }
}
