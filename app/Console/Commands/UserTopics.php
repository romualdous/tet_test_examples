<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Console\Command;

class UserTopics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topics:user 
                            {user : user ID} 
                            {--listener : change user type to listener}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set user type to "listener", connect user to all topics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $topics = Topic::all();

        $user = User::find($this->argument('user'));

        $user->topics()->saveMany($topics);

        $this->info($user->full_name . ' connected to all topics.');


        if ($this->option('listener')) {

            $user->type = 'listener';

            $user->save();

            $this->info($this->info($user->full_name . ' is now listener.'));
        }
    }
}
