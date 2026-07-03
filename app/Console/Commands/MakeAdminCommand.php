<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email}')]
#[Description('Promote a user to admin by email address')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        $user->update(['is_admin' => true]);

        $this->info("User {$email} is now an admin.");

        return self::SUCCESS;
    }
}
