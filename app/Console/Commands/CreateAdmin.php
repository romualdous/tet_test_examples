<?php

namespace App\Console\Commands;

use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user with administrative capabilities';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $minPasswordLength = config('administrator.settings.passwords.min_length');

        $email = $this->ask('Provide an e-mail address for new administrator account');
        $password = $this->ask('Provide a password for new administrator account');

        try {
            Validator::make([
                'email'    => $email,
                'password' => $password
            ], [
                'email'    => 'required|email|unique:users,email',
                'password' => "required|min:{$minPasswordLength}"
            ], [
                'email.required'    => 'No e-email provided',
                'email.email'       => 'Provided data must be legit e-mail address',
                'email.unique'      => 'User with provided e-mail address already exists',
                'password.required' => 'No password provided',
                'password.min'      => "Min allowed password length is {$minPasswordLength} characters"
            ])->validate();
        } catch (ValidationException $exception) {
            $this->displayAllErrors(
                collect($exception->validator->getMessageBag()->all())
            );

            return -1;
        }

        $user = User::forceCreate([
            'email'    => $email,
            'password' => Hash::make($password),
            'type'     => 'listener'
        ]);

        $user->createToken($email, ['admin-access']);

        $this->info('Administrator successfully created!');

        return 0;
    }

    /**
     * @param Collection $errors
     */
    private function displayAllErrors(Collection $errors)
    {
        $errors->each(fn ($error) => $this->error($error));
    }
}
