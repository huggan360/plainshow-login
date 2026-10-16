<?php

use App\Http\Controllers\OAuth\DiscoveryController;
use App\Http\Controllers\OAuth\JwksController;
use App\Http\Controllers\OAuth\UserinfoController;
use App\Http\Controllers\Internal\TokensController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;

// Machine endpoints: no session, no CSRF. Clients authenticate with their
// secret (token) or a bearer token (userinfo).
Route::prefix('oauth')->group(function () {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->middleware('throttle:token')
        ->name('passport.token');
    Route::get('/jwks', JwksController::class)->name('openid.jwks');
    Route::match(['get', 'post'], '/userinfo', UserinfoController::class)
        ->middleware('auth:api')
        ->name('openid.userinfo');
});

Route::get('/.well-known/openid-configuration', DiscoveryController::class)->name('openid.discovery');

// Core's account page: personal API tokens, over signed requests only.
Route::prefix('internal')->middleware('throttle:60,1')->group(function () {
    Route::get('/tokens', [TokensController::class, 'index']);
    Route::post('/tokens', [TokensController::class, 'store']);
    Route::delete('/tokens/{token}', [TokensController::class, 'destroy'])->where('token', '[a-f0-9]{80}');
});
