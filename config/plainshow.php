<?php

use PlainShow\Kit\Platform;

return [
    // Every app's address comes from the platform file (kit Platform).
    'core_url' => env('PLAINSHOW_CORE_URL', Platform::url('core') ?? 'https://plainshow.se'),
    // Shared with core for signed internal requests (personal API tokens).
    'internal_secret' => env('PLAINSHOW_INTERNAL_SECRET', ''),
    'account_helper' => env('PLAINSHOW_ACCOUNT_HELPER', '/usr/local/libexec/plainshow-account-helper'),
    'protected_pi_username' => env('PLAINSHOW_PROTECTED_PI_USERNAME', 'hugohansson'),

    // Where people may be sent back to after signing in or out.
    'return_hosts' => array_values(array_unique(array_merge(
        [Platform::domain(), '*.'.Platform::domain()],
        array_filter(array_map(fn ($url) => parse_url($url, PHP_URL_HOST), Platform::urls())),
    ))),

    // Lock an account for a while after this many failed passwords.
    'lockout_attempts' => (int) env('PLAINSHOW_LOCKOUT_ATTEMPTS', 10),
    'lockout_minutes' => (int) env('PLAINSHOW_LOCKOUT_MINUTES', 15),

    // When true, administrators cannot sign in until they set up 2FA.
    'require_admin_two_factor' => (bool) env('PLAINSHOW_REQUIRE_ADMIN_2FA', false),

    'password_reset_minutes' => 60,
];
