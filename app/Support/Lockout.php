<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/** Per-account lockout on top of the per-IP limits, so slow guessing stops too. */
class Lockout
{
    private static function key(string $login): string
    {
        return 'lockout:'.hash('sha256', mb_strtolower(trim($login)));
    }

    public static function locked(string $login): bool
    {
        return RateLimiter::tooManyAttempts(self::key($login), (int) config('plainshow.lockout_attempts'));
    }

    public static function secondsLeft(string $login): int
    {
        return RateLimiter::availableIn(self::key($login));
    }

    public static function fail(string $login): void
    {
        RateLimiter::hit(self::key($login), (int) config('plainshow.lockout_minutes') * 60);
    }

    public static function clear(string $login): void
    {
        RateLimiter::clear(self::key($login));
    }
}
