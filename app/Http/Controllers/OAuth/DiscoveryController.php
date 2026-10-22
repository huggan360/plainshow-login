<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** OpenID Connect discovery, listing only what this server actually supports. */
class DiscoveryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $issuer = rtrim((string) config('app.url'), '/');

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'userinfo_endpoint' => $issuer.'/oauth/userinfo',
            'jwks_uri' => $issuer.'/oauth/jwks',
            'end_session_endpoint' => $issuer.'/logout',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token', 'client_credentials'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => array_keys((array) config('openid.passport.tokens_can')),
            'claims_supported' => [
                'sub', 'iss', 'aud', 'exp', 'iat', 'nonce', 'name', 'given_name', 'family_name',
                'preferred_username', 'picture', 'email', 'email_verified', 'updated_at',
                'ps_role', 'ps_services', 'ps_caps', 'ps_pi_user', 'ps_cloud_uid', 'ps_groups',
            ],
            'frontchannel_logout_supported' => true,
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
