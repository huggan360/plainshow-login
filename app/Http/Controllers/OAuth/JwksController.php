<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\OAuth\SigningKey;
use Illuminate\Http\JsonResponse;

class JwksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json(['keys' => [SigningKey::jwk()]], 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
