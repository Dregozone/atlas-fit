<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password:reset {email : The email address of the user} {newPassword : The new plain text password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the password for a user account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $newPassword = $this->argument('newPassword');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email address: {$email}");

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => $newPassword,
        ])->save();

        $this->info("Password has been reset for {$email}.");

        return self::SUCCESS;
    }
}
