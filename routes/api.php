<?php

use App\Http\Controllers\OAuth\DiscoveryController;
use App\Http\Controllers\OAuth\JwksController;
use App\Http\Controllers\OAuth\UserinfoController;
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
