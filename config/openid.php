<?php

return [
    'passport' => [
        // Scopes every PlainShow app may request.
        'tokens_can' => [
            'openid' => 'Sign you in',
            'profile' => 'Your name, username and picture',
            'email' => 'Your email address',
            'plainshow' => 'Your PlainShow role and access',
        ],
    ],

    // Claims released for the `plainshow` scope, on top of the standard sets.
    'custom_claim_sets' => [
        'plainshow' => ['ps_role', 'ps_services', 'ps_caps', 'ps_pi_user', 'ps_cloud_uid'],
    ],

    'repositories' => [
        'identity' => \OpenIDConnect\Repositories\IdentityRepository::class,
        'scope' => \App\OAuth\ScopeRepository::class,
    ],

    // RS256, so apps verify tokens with the published public key. The package
    // default (HMAC with the private key as secret) cannot be verified by clients.
    'signer' => \Lcobucci\JWT\Signer\Rsa\Sha256::class,

    'routes' => [
        'discovery' => false, // Served by App\Http\Controllers\OAuth\DiscoveryController (the package swaps its two flags).
        // Served by App\Http\Controllers\OAuth\JwksController with a key id.
        'jwks' => false,
    ],
];
