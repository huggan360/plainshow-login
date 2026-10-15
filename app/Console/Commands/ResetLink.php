<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Prints a one-hour password reset link. Core's admin page does the same. */
class ResetLink extends Command
{
    protected $signature = 'plainshow:reset-link {username}';

    protected $description = 'Create a password reset link for an account';

    public function handle(): int
    {
        $user = User::where('username', $this->argument('username'))->first();
        if (! $user) {
            $this->error('No such account.');

            return self::FAILURE;
        }
        $token = Str::random(64);
        DB::table('login_password_reset_tokens')->updateOrInsert(
            ['username' => $user->username],
            ['token' => hash('sha256', $token), 'created_at' => now()],
        );
        Audit::record('password.reset_link', $user->id, 'cli');
        $this->line(rtrim((string) config('app.url'), '/').'/reset/'.$token.'?u='.rawurlencode($user->username));

        return self::SUCCESS;
    }
}
