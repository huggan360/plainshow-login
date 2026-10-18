<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\DeviceController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Middleware\EnsureClientAccess;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AuthorizationController;

Route::redirect('/', config('plainshow.core_url'));

// Signed-in visitors are sent on to their return address by the controller.
Route::get('/login', [LoginController::class, 'show'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor');
    Route::post('/two-factor', [TwoFactorController::class, 'store'])->middleware('throttle:two-factor');
    Route::get('/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset', [ResetPasswordController::class, 'store'])->middleware('throttle:login')->name('password.update');
});

Route::match(['get', 'post'], '/logout', LogoutController::class)->name('logout');
Route::get('/devices/connect', [DeviceController::class, 'connect'])->name('devices.connect');
Route::get('/devices/result', [DeviceController::class, 'result'])->name('devices.result');

// Only the authorize endpoint lives here (it needs the session). Token, JWKS
// and userinfo are in routes/api.php. Passport's client and token management
// routes stay unregistered (see AppServiceProvider).
Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])
    ->middleware(EnsureClientAccess::class)
    ->name('passport.authorizations.authorize');
