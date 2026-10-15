<?php

namespace App\Support;

/** Only PlainShow addresses may be returned to, so login is never an open redirect. */
class ReturnUrl
{
    public static function allowed(?string $url): bool
    {
        if (! is_string($url) || $url === '' || preg_match('/[\x00-\x20\\\\]/', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || empty($parts['host'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        foreach (config('plainshow.return_hosts') as $pattern) {
            if ($host === $pattern || (str_starts_with($pattern, '*.') && str_ends_with($host, substr($pattern, 1)))) {
                return true;
            }
        }

        return false;
    }

    public static function or(?string $url, ?string $fallback = null): string
    {
        return self::allowed($url) ? $url : ($fallback ?? rtrim((string) config('plainshow.core_url'), '/').'/');
    }
}
