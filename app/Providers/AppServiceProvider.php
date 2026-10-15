<?php

namespace App\Providers;

use App\Models\OAuthClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Passport's own routes include client and token management for any
        // signed-in user. Only authorize and token are registered, in routes/web.php.
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Passport::useClientModel(OAuthClient::class);
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('login:'.mb_strtolower((string) $request->input('login')).'|'.$request->ip()),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(6)->by('2fa:'.$request->session()->getId()));
        RateLimiter::for('token', fn (Request $request) => Limit::perMinute(60)->by('token:'.$request->ip()));
    }
}
