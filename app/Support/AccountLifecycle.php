<?php

namespace App\Support;

use App\Models\User;
use RuntimeException;
use Symfony\Component\Process\Process;

class AccountLifecycle
{
    public static function syncPassword(User $user, string $password): void
    {
        if (! PlatformAccess::canUseClient($user, 'web') || ! is_string($user->pi_username)
            || $user->pi_username === '' || $user->pi_username === config('plainshow.protected_pi_username')) {
            return;
        }

        $process = new Process(['sudo', '-n', config('plainshow.account_helper'), 'pi', 'password', $user->pi_username, (string) $user->id]);
        $process->setTimeout(130);
        $process->setInput($password);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Server password sync failed: '.trim($process->getErrorOutput()));
        }
    }
}
