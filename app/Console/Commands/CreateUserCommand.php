<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:manual:create-user {email} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: $email");
            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::random();

        User::factory()->create([
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $this->info("User created with password: $password");

        return self::SUCCESS;
    }
}
