<?php

namespace App\Http\Controllers\OAuth;

use App\Entities\IdentityEntity;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Standard OpenID Connect userinfo. Nextcloud reads the profile from here. */
class UserinfoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('api');
        if (! $user || ! $user->isActive()) {
            return response()->json(['error' => 'invalid_token'], 401);
        }
        $token = $user->token();
        $scopes = $token ? (array) $token->scopes : [];

        $claims = ['sub' => (string) $user->id];
        $all = IdentityEntity::claimsFor($user);
        $sets = [
            'profile' => ['name', 'given_name', 'family_name', 'preferred_username', 'picture', 'updated_at'],
            'email' => ['email', 'email_verified'],
            'plainshow' => ['ps_role', 'ps_services', 'ps_caps', 'ps_pi_user'],
        ];
        foreach ($sets as $scope => $names) {
            if (in_array($scope, $scopes, true)) {
                foreach ($names as $name) {
                    if (array_key_exists($name, $all)) {
                        $claims[$name] = $all[$name];
                    }
                }
            }
        }

        return response()->json($claims, 200, [], JSON_UNESCAPED_SLASHES)->header('Cache-Control', 'no-store');
    }
}
