<?php

return [
    'core_url' => env('PLAINSHOW_CORE_URL', 'https://plainshow.se'),
    // Shared with core for signed internal requests (personal API tokens).
    'internal_secret' => env('PLAINSHOW_INTERNAL_SECRET', ''),

    // Where people may be sent back to after signing in or out.
    'return_hosts' => ['plainshow.se', '*.plainshow.se'],

    // Lock an account for a while after this many failed passwords.
    'lockout_attempts' => (int) env('PLAINSHOW_LOCKOUT_ATTEMPTS', 10),
    'lockout_minutes' => (int) env('PLAINSHOW_LOCKOUT_MINUTES', 15),

    // When true, administrators cannot sign in until they set up 2FA.
    'require_admin_two_factor' => (bool) env('PLAINSHOW_REQUIRE_ADMIN_2FA', false),

    'password_reset_minutes' => 60,
];
